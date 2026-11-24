<?php

namespace Database\Seeders;

use App\Enums\EstadoCotizacion;
use App\Enums\EstadoUsuario;
use App\Enums\RolNombre;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EmpresaConfiguracion;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\CarteraService;
use App\Services\CatalogoService;
use App\Services\FacturacionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de prueba para el prototipo de Privileg Distribuciones.
 *
 * Las fechas son relativas al día en que se ejecuta el seeder, de modo que
 * siempre existan facturas al día, por vencer y en mora (1-30, 31-60, 61-90 y
 * más de 90 días) para pruebas y capturas de pantalla.
 *
 * Todas las cuentas sembradas usan la contraseña de pruebas USUARIOS_PASSWORD.
 * Cámbiela antes de usar el sistema con datos reales.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public const USUARIOS_PASSWORD = 'Privileg2026!';

    public function __construct(
        private readonly FacturacionService $facturacion,
        private readonly CarteraService $cartera,
        private readonly CatalogoService $catalogo,
    ) {}

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->sembrarEmpresa();
            $roles = $this->sembrarRoles();
            $usuarios = $this->sembrarUsuarios($roles);
            $clientes = $this->sembrarClientes();
            $productos = $this->sembrarProductos();
            $this->sembrarTransacciones($usuarios, $clientes, $productos);
        });
    }

    private function sembrarEmpresa(): void
    {
        (new EmpresaConfiguracion)
            ->forceFill(['id' => EmpresaConfiguracion::ID_UNICO])
            ->fill([
                'razon_social' => 'Privileg Distribuciones Ltda. S.A.',
                'ruc' => '1891700000001',
                'direccion' => 'Av. Bolivariana y Los Shyris, Ambato, Tungurahua - Ecuador',
                'telefono' => '032 420 000',
                'correo' => 'facturacion@privileg.com',
            ])
            ->save();
    }

    /**
     * @return array<string, Rol>
     */
    private function sembrarRoles(): array
    {
        $roles = [];

        foreach (RolNombre::cases() as $rol) {
            $roles[$rol->value] = Rol::create(['nombre' => $rol]);
        }

        return $roles;
    }

    /**
     * @param  array<string, Rol>  $roles
     * @return array<string, Usuario>
     */
    private function sembrarUsuarios(array $roles): array
    {
        $cuentas = [
            'jose' => ['José Miguel Orellana Romo', 'jmorellana@privileg.com', RolNombre::Administrador, EstadoUsuario::Activo],
            'juan' => ['Juan Esteban Santacruz Erazo', 'jsantacruz@privileg.com', RolNombre::Vendedor, EstadoUsuario::Activo],
            'carlos' => ['Carlos Mendoza', 'carlos.gerencia@privileg.com', RolNombre::Gerente, EstadoUsuario::Activo],
            'patricia' => ['Patricia Villacís', 'patricia.gerencia@privileg.com', RolNombre::Gerente, EstadoUsuario::Activo],
            'diego' => ['Diego Naranjo', 'diego.gerencia@privileg.com', RolNombre::Gerente, EstadoUsuario::Activo],
            'andrea' => ['Andrea Castillo', 'andrea.gerencia@privileg.com', RolNombre::Gerente, EstadoUsuario::Activo],
            'ana' => ['Ana Torres', 'ana.contador@privileg.com', RolNombre::Contador, EstadoUsuario::Activo],
            'luis' => ['Luis Pérez', 'luis.ventas@privileg.com', RolNombre::Vendedor, EstadoUsuario::Activo],
            'marco' => ['Marco Salazar', 'marco.ventas@privileg.com', RolNombre::Vendedor, EstadoUsuario::Inactivo],
        ];

        $usuarios = [];

        foreach ($cuentas as $clave => [$nombre, $email, $rol, $estado]) {
            $usuarios[$clave] = Usuario::create([
                'rol_id' => $roles[$rol->value]->id,
                'nombre_completo' => $nombre,
                'email' => $email,
                'password' => self::USUARIOS_PASSWORD,
                'estado' => $estado,
            ]);
        }

        return $usuarios;
    }

    /**
     * Clientes ficticios de Ambato (Tungurahua) con identificaciones de prueba válidas.
     *
     * @return array<string, Cliente>
     */
    private function sembrarClientes(): array
    {
        $datos = [
            'ortiz' => ['1893947597001', 'Comercial Ortiz Cía. Ltda.', 'Av. Cevallos y Montalvo, Ambato', '032412890', 'cobranzas@comercialortiz.ec'],
            'minimarket' => ['1835607746001', 'Minimarket Central', 'Calle Bolívar 14-22 y Guayaquil, Ambato', '032823112', 'minimarketcentral@correo.ec'],
            'distribuidora' => ['1894087516001', 'Distribuidora Ambato S.A.', 'Parque Industrial Ambato, Calle C y Av. 5', '032991822', 'pagos@distriambato.ec'],
            'volcan' => ['1822733364001', 'Ferretería El Volcán', 'Av. Los Shyris y Pasaje Tungurahua, Ambato', '032845910', 'ferrevolcan@correo.ec'],
            'merced' => ['1891699405001', 'Supermercado La Merced S.A.', 'Av. 12 de Noviembre 08-45, Ambato', '032420778', 'contabilidad@lamerced.ec'],
            'ficoa' => ['1834919175001', 'Bazar y Papelería Ficoa', 'Av. Los Guaytambos, sector Ficoa, Ambato', '032461230', null],
            'lucho' => ['1836910602001', 'Tienda Don Lucho', 'Barrio La Península, calle Quiz Quiz, Ambato', '0987654321', null],
            'huachi' => ['1893235538001', 'Comisariato Huachi Chico Cía. Ltda.', 'Av. Atahualpa y Río Cutuchi, Huachi Chico', '032585640', 'compras@huachichico.ec'],
            'santarosa' => ['1806657795001', 'Abarrotes Santa Rosa', 'Parroquia Santa Rosa, vía a Guaranda km 8', '032750118', null],
            'atocha' => ['1818561332001', 'Panificadora Atocha', 'Av. Rodrigo Pachano, sector Atocha, Ambato', '032444906', 'panatocha@correo.ec'],
            'miraflores' => ['1890806887001', 'Hostal y Cafetería Miraflores S.A.', 'Av. Miraflores 02-117, Ambato', '032843577', 'admin@hostalmiraflores.ec'],
            'ingahurco' => ['1807455645001', 'Farmacia Ingahurco', 'Av. Manuelita Sáenz y Pedro Vásconez, Ingahurco', '032521046', null],
            'tunas' => ['1836809234', 'Restaurante Las Tunas', 'Pelileo, calle 22 de Julio y Padre Chacón', '0991234567', null],
            'lopez' => ['1804642492', 'María Fernanda López Sánchez', 'Ciudadela España, calle Burgos, Ambato', '0998877665', 'mflopez@correo.ec'],
        ];

        $clientes = [];

        foreach ($datos as $clave => [$ruc, $razonSocial, $direccion, $telefono, $correo]) {
            $clientes[$clave] = Cliente::create([
                'ruc_cedula' => $ruc,
                'razon_social' => $razonSocial,
                'direccion' => $direccion,
                'telefono' => $telefono,
                'correo' => $correo,
            ]);
        }

        return $clientes;
    }

    /**
     * Catálogo de distribución (abarrotes, limpieza, bebidas y servicios).
     * IVA 0% para alimentos de la canasta básica e IVA 15% para el resto.
     *
     * @return array<string, Producto>
     */
    private function sembrarProductos(): array
    {
        $catalogo = [
            ['ABR-001', 'Arroz blanco grano largo · saco 25 kg', '23.50', '0', '0', true],
            ['ABR-002', 'Azúcar blanca · saco 50 kg', '42.00', '0', '0', true],
            ['ABR-003', 'Harina de trigo panificable · saco 50 kg', '38.75', '0', '0', true],
            ['ABR-004', 'Aceite vegetal 1 L · caja x12', '27.60', '5', '0', true],
            ['ABR-005', 'Fideo tallarín 400 g · caja x20', '15.80', '0', '0', true],
            ['ABR-006', 'Atún en aceite 180 g · caja x48', '68.90', '5', '15', true],
            ['ABR-007', 'Leche en polvo entera 1 kg · caja x10', '72.40', '0', '0', true],
            ['ABR-008', 'Avena en hojuelas 400 g · caja x24', '19.90', '0', '0', false],
            ['LIM-001', 'Detergente en polvo 1 kg · caja x10', '21.30', '10', '15', true],
            ['LIM-002', 'Cloro 1 galón · caja x4', '9.80', '0', '15', true],
            ['LIM-003', 'Papel higiénico 4 rollos · paca x12', '14.25', '0', '15', true],
            ['BEB-001', 'Agua sin gas 600 ml · paca x24', '6.90', '0', '15', true],
            ['BEB-002', 'Bebida gaseosa 3 L · paca x6', '11.70', '0', '15', true],
            ['HIG-001', 'Jabón de tocador 110 g · caja x24', '16.40', '5', '15', true],
            ['SRV-001', 'Servicio de flete fuera de Ambato', '25.00', '0', '15', true],
        ];

        $productos = [];

        foreach ($catalogo as [$codigo, $nombre, $precio, $descuento, $iva, $activo]) {
            $productos[$codigo] = $this->catalogo->guardarProducto([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'precio_base' => $precio,
                'porcentaje_descuento' => $descuento,
                'porcentaje_iva' => $iva,
                'activo' => $activo,
            ]);
        }

        return $productos;
    }

    /**
     * @param  array<string, Usuario>  $usuarios
     * @param  array<string, Cliente>  $clientes
     * @param  array<string, Producto>  $productos
     */
    private function sembrarTransacciones(array $usuarios, array $clientes, array $productos): void
    {
        $hoy = today();

        $lineas = fn (array $items): array => array_map(
            fn (array $item): array => [
                'producto_id' => $productos[$item[0]]->id,
                'cantidad' => $item[1],
                'porcentaje_descuento' => $item[2] ?? null,
                'porcentaje_iva' => $item[3] ?? null,
            ],
            $items,
        );

        // Cotizaciones: [cliente, días atrás, líneas, estado final, vendedor]
        $cotizaciones = [
            'huachi' => ['huachi', 12, [['ABR-001', '40'], ['ABR-002', '20'], ['ABR-004', '30'], ['LIM-001', '25'], ['BEB-001', '60']], EstadoCotizacion::Aprobada, 'juan'],
            'minimarket' => ['minimarket', 25, [['ABR-006', '6'], ['BEB-002', '30'], ['LIM-003', '20']], EstadoCotizacion::Pendiente, 'juan'],
            'volcan' => ['volcan', 12, [['LIM-002', '40'], ['LIM-001', '30'], ['SRV-001', '2']], EstadoCotizacion::Aprobada, 'luis'],
            'miraflores' => ['miraflores', 30, [['BEB-001', '50'], ['HIG-001', '10']], EstadoCotizacion::Rechazada, 'juan'],
            'ingahurco' => ['ingahurco', 2, [['HIG-001', '12'], ['LIM-003', '8']], EstadoCotizacion::Pendiente, 'luis'],
            'lopez' => ['lopez', 1, [['ABR-007', '1'], ['ABR-005', '2']], EstadoCotizacion::Pendiente, 'jose'],
        ];

        $cotizacionesCreadas = [];

        foreach ($cotizaciones as $clave => [$cliente, $diasAtras, $items, $estado, $vendedor]) {
            $cotizacion = $this->facturacion->registrarCotizacion([
                'cliente_id' => $clientes[$cliente]->id,
                'fecha_emision' => $hoy->copy()->subDays($diasAtras)->toDateString(),
                'lineas' => $lineas($items),
            ], $usuarios[$vendedor]);

            if ($estado !== EstadoCotizacion::Pendiente) {
                $cotizacion->update(['estado' => $estado]);
            }

            $cotizacionesCreadas[$clave] = $cotizacion;
        }

        /*
         * Ventas: [cliente, tipo, días desde emisión, plazo, líneas, vendedor, extra]
         * El usuario indicado es el vendedor; 'registra' permite que otro usuario la registre.
         * extra: 'abonos' => [[días tras emisión, porcentaje del total o 'saldo', método, referencia]],
         *        'metodo'/'referencia' (contado), 'anular' o 'cotizacion'.
         */
        $ventas = [
            // Crédito con mora > 90 días y abono parcial
            ['santarosa', 'credito', 150, 45, [['ABR-001', '20'], ['ABR-002', '10'], ['LIM-002', '5']], 'luis', ['abonos' => [[20, 25, 'efectivo', null]]]],
            // Crédito con mora 61-90 días sin abonos
            ['tunas', 'credito', 105, 30, [['BEB-002', '25'], ['ABR-006', '2'], ['ABR-004', '4']], 'juan', []],
            // Crédito con mora 31-60 días y abono parcial
            ['merced', 'credito', 80, 30, [['ABR-007', '12'], ['LIM-001', '20'], ['HIG-001', '15']], 'jose', ['abonos' => [[35, 30, 'cheque', 'CH-004512']]]],
            // Crédito totalmente pagado en dos abonos
            ['merced', 'credito', 75, 30, [['ABR-001', '50'], ['ABR-003', '20'], ['ABR-005', '30']], 'jose', ['abonos' => [[20, 50, 'transferencia', 'TRF-889120'], [29, 'saldo', 'cheque', 'CH-004530']]]],
            // Contado
            ['minimarket', 'contado', 58, 0, [['BEB-001', '20'], ['LIM-003', '6']], 'juan', ['metodo' => 'efectivo']],
            // Crédito pagado
            ['ortiz', 'credito', 50, 30, [['ABR-002', '8'], ['ABR-004', '10']], 'juan', ['abonos' => [[25, 'saldo', 'transferencia', 'TRF-901244']]]],
            // Crédito con mora 1-30 días (18 días) sin abonos
            ['ortiz', 'credito', 48, 30, [['ABR-006', '6'], ['LIM-001', '12']], 'juan', []],
            // Contado
            ['atocha', 'contado', 41, 0, [['ABR-003', '4']], 'luis', ['metodo' => 'transferencia', 'referencia' => 'TRF-903117']],
            // Crédito con mora 1-30 días (10 días) y abono parcial
            ['minimarket', 'credito', 40, 30, [['BEB-002', '20'], ['ABR-005', '15']], 'juan', ['registra' => 'ana', 'abonos' => [[12, 50, 'efectivo', null]]]],
            // Crédito por vencer (5 días) con abono parcial
            ['miraflores', 'credito', 40, 45, [['BEB-001', '40'], ['HIG-001', '8']], 'luis', ['abonos' => [[15, 30, 'efectivo', null]]]],
            // Crédito con mora 1-30 días (5 días)
            ['ficoa', 'credito', 35, 30, [['LIM-003', '15'], ['HIG-001', '6']], 'luis', []],
            // Crédito por vencer (3 días) sin abonos
            ['volcan', 'credito', 27, 30, [['LIM-002', '30'], ['LIM-001', '20'], ['SRV-001', '1']], 'luis', []],
            // Contado
            ['ficoa', 'contado', 20, 0, [['LIM-003', '10'], ['BEB-001', '12']], 'luis', ['metodo' => 'tarjeta', 'referencia' => 'VOUCHER-55120']],
            // Crédito al día con abono parcial
            ['distribuidora', 'credito', 15, 45, [['ABR-001', '30', '8'], ['ABR-007', '6'], ['ABR-006', '4']], 'jose', ['abonos' => [[10, 45, 'transferencia', 'TRF-915502']]]],
            // Crédito al día, facturado desde cotización, con abono
            ['huachi', 'credito', 10, 30, null, 'juan', ['cotizacion' => 'huachi', 'abonos' => [[5, 30, 'cheque', 'CH-004611']]]],
            // Contado
            ['lucho', 'contado', 9, 0, [['ABR-005', '3'], ['BEB-002', '2']], 'juan', ['metodo' => 'efectivo']],
            // Crédito anulado
            ['atocha', 'credito', 6, 30, [['ABR-003', '5']], 'luis', ['anular' => true]],
            // Contado
            ['ingahurco', 'contado', 3, 0, [['HIG-001', '20'], ['LIM-002', '10']], 'luis', ['metodo' => 'transferencia', 'referencia' => 'TRF-918830']],
            ['santarosa', 'contado', 1, 0, [['ABR-001', '5'], ['ABR-004', '3']], 'juan', ['metodo' => 'efectivo']],
            // Crédito recién emitido
            ['lopez', 'credito', 0, 15, [['ABR-007', '1'], ['ABR-005', '2']], 'jose', []],
        ];

        foreach ($ventas as [$cliente, $tipo, $diasAtras, $plazo, $items, $vendedor, $extra]) {
            $emision = $hoy->copy()->subDays($diasAtras);
            $cotizacion = isset($extra['cotizacion']) ? $cotizacionesCreadas[$extra['cotizacion']] : null;

            $factura = $this->facturacion->registrarFactura([
                'cliente_id' => $clientes[$cliente]->id,
                'numero_factura' => $this->facturacion->sugerirNumeroFactura(),
                'tipo_transaccion' => $tipo,
                'fecha_emision' => $emision->toDateString(),
                'fecha_vencimiento' => $emision->copy()->addDays($plazo)->toDateString(),
                'lineas' => $cotizacion ? $this->facturacion->lineasDesdeCotizacion($cotizacion) : $lineas($items),
                'metodo_pago' => $extra['metodo'] ?? null,
                'comprobante_referencia' => $extra['referencia'] ?? null,
                'cotizacion_id' => $cotizacion?->id,
                'vendedor_id' => $cotizacion?->vendedor_id ?? $usuarios[$vendedor]->id,
            ], $usuarios[$extra['registra'] ?? $vendedor]);

            foreach ($extra['abonos'] ?? [] as [$diasDespues, $porcentaje, $metodo, $referencia]) {
                $monto = $porcentaje === 'saldo'
                    ? $factura->fresh()->saldo()
                    : bcadd(bcround(bcdiv(bcmul((string) $factura->total, (string) $porcentaje, 4), '100', 4), 2), '0', 2);

                $this->cartera->registrarAbono($factura, [
                    'fecha_pago' => $emision->copy()->addDays($diasDespues)->toDateString(),
                    'monto_abonado' => $monto,
                    'metodo_pago' => $metodo,
                    'comprobante_referencia' => $referencia,
                ], $usuarios['ana']);
            }

            if ($extra['anular'] ?? false) {
                $this->facturacion->anularFactura($factura);
            }
        }
    }
}
