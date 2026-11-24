# Privileg Contable — Cartera por Cobrar

Aplicación web contable para **Privileg Distribuciones Ltda. S.A.** (Ambato): catálogo de productos, cotizaciones y facturas maestro-detalle (contado y crédito), abonos, control de mora y reportes en PDF y Excel. Proyecto de titulación TESA — Desarrollo de Software.

**Stack:** Laravel 13 · PHP 8.5 · PostgreSQL 18 · Blade + Tailwind CSS 4 + Alpine.js · laravel-dompdf · maatwebsite/excel 4.

## Puesta en marcha (servidor local)

1. Configure en `.env` la conexión `DB_*` (PostgreSQL).
2. Cree las bases `privileg_db` (sistema) y `privileg_db_test` (pruebas automatizadas).
3. Ejecute:

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
```

Con Laravel Herd el sistema queda disponible en `http://privileg-contable.test`. Las fuentes se compilan dentro de `public/build`, por lo que la aplicación funciona en la LAN sin acceso a internet.

Los datos de la empresa (razón social, RUC, dirección, teléfono, correo) se administran desde **Configuración** con la cuenta de Administrador General y se imprimen en todos los PDFs y Excels.

## Cuentas de prueba (sólo para el prototipo)

Todas usan la contraseña definida en `Database\Seeders\DatabaseSeeder::USUARIOS_PASSWORD` (`Privileg2026!`). Cámbiela antes de cargar datos reales.

| Usuario | Correo | Rol |
|---|---|---|
| José Miguel Orellana Romo | jmorellana@privileg.com | Administrador General |
| Juan Esteban Santacruz Erazo | jsantacruz@privileg.com | Vendedor |
| Carlos Mendoza | carlos.gerencia@privileg.com | Gerente |
| Patricia Villacís | patricia.gerencia@privileg.com | Gerente |
| Diego Naranjo | diego.gerencia@privileg.com | Gerente |
| Andrea Castillo | andrea.gerencia@privileg.com | Gerente |
| Ana Torres | ana.contador@privileg.com | Contador |
| Luis Pérez | luis.ventas@privileg.com | Vendedor |
| Marco Salazar | marco.ventas@privileg.com | Vendedor (inactivo: no puede ingresar) |

El seeder crea los datos de la empresa, 15 productos (uno inactivo), 14 clientes ficticios de Ambato, 6 cotizaciones y 20 ventas con fechas relativas al día en que se ejecuta: contado, crédito pagado, crédito con abonos, por vencer y en mora (1-30, 31-60, 61-90 y más de 90 días). Ejecute `php artisan migrate:fresh --seed` para regenerarlos.

### Vendedor y descuentos en documentos

Cada factura y cotización registra dos personas: el **vendedor** (quien realizó la venta, se elige en el formulario) y quien la **registró** en el sistema. El PDF muestra "Vendedor: NOMBRE". En cada línea se puede ajustar el precio unitario (sin IVA) y el % de descuento, y elegir la tarifa de IVA (0%, 5% o 15%); los valores se cargan del catálogo y los montos siempre los calcula el servidor. Al facturar una cotización se conservan los precios cotizados.

### Menú lateral

En escritorio el menú es una franja de íconos que se despliega al acercar el mouse o al navegar con Tab. El botón "Menú: Desplegable / Fijo" lo deja siempre abierto; la preferencia se guarda en cada navegador.

### Tema claro y oscuro

El botón del sol / la luna (barra superior y pantalla de inicio de sesión) cambia entre el tema oscuro (predeterminado) y el tema claro "Porcelana". La elección se guarda en la cookie `privileg_tema` de cada navegador durante un año, y el servidor pinta la página ya con ese tema.

### Listados y paginación

Clientes, Productos, Usuarios, Transacciones, Documentos, Cartera y el historial de facturas de cada cliente muestran 10 filas por página. Con "Filas por página" (visible en todo listado con registros) se elige 10, 20, 50 o 100; la elección se recuerda durante la sesión en todos los listados (opciones en `config/contable.php`).

### Reportes y vista previa

Hay cinco reportes en PDF y Excel: **Reporte general del periodo** (ventas y cobros, ventas por vendedor y cartera con antigüedad y principales deudores en un solo documento), ventas, ventas por vendedor, cartera por cobrar y estado de cuenta por cliente.

Cada reporte tiene su botón "Vista previa" (y hay uno general junto a los filtros) que muestra, antes de descargar, cómo queda con los filtros elegidos:

- **PDF**: la misma plantilla del PDF sobre una hoja A4.
- **Excel**: el mismo libro `.xlsx`, hoja por hoja, con la cabecera de la empresa y los montos en formato de moneda.

En la ventana se cambia de reporte con las pestañas (General, Ventas, Por vendedor, Cartera, Estado de cuenta), de formato con "PDF | Excel", y se descarga el que se está viendo.

### Recuperación de contraseña

El enlace "¿Olvidó su contraseña?" envía un correo con un enlace válido por 60 minutos. En local `MAIL_MAILER=log`, así que el correo no sale: el enlace queda escrito al final de `storage/logs/laravel.log`. Para enviar correos reales configure `MAIL_*` con un servidor SMTP.

## Permisos por rol

El **Administrador General** tiene acceso absoluto a todos los módulos (`Gate::before`). Las reglas contables (no abonar más que el saldo, no anular facturas con abonos, etc.) se aplican igual para todos.

| Acción | Gerente | Contador | Vendedor |
|---|:-:|:-:|:-:|
| Dashboard financiero completo | ✓ | ✓ | Resumen propio |
| Seguridad y Usuarios (sin tocar administradores) | ✓ | — | — |
| Configuración de empresa | — | — | — |
| Clientes: ver, registrar y editar | ✓ | ✓ | ✓ |
| Clientes: eliminar (sin documentos) | ✓ | — | — |
| Productos: consultar catálogo | ✓ | ✓ | ✓ |
| Productos: crear, editar, eliminar | ✓ | — | — |
| Registrar facturas (contado / crédito) | ✓ | ✓ | ✓ |
| Elegir el vendedor de la venta o cotización | ✓ | ✓ | ✓ |
| Ajustar precio unitario, % de descuento e IVA en cada línea | ✓ | ✓ | ✓ |
| Guardar ese ajuste también en el catálogo | ✓ | — | — |
| Ver facturas | Todas | Todas | Las que vendió o registró |
| Anular factura sin abonos | ✓ | ✓ | — |
| Registrar abonos | ✓ | ✓ | — |
| Revertir abonos | ✓ | — | — |
| Emitir / editar cotizaciones | ✓ | — | Las que vendió o registró |
| Cartera y alertas de mora | ✓ | ✓ | — |
| Reportes PDF / Excel con vista previa (general, ventas, por vendedor, cartera, estado de cuenta) | ✓ | ✓ | — |

## Pruebas automatizadas

```bash
php artisan test
```

Usan la base `privileg_db_test` en PostgreSQL (configurada en `phpunit.xml`).

## Alcance

No incluye facturación electrónica con el SRI, inventario ni kardex físico, ni contabilidad general (asientos o diarios). Los PDF y Excel son documentos internos de control de cartera y ventas.
