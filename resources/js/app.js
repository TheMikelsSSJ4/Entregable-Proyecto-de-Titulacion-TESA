/**
 * Interacciones del sistema contable.
 *
 * - Alpine.js: cálculo en vivo de precios (productos) y documentos maestro-detalle
 *   (cotizaciones y facturas), y el "ojito" de la contraseña.
 * - JavaScript simple: menú móvil, confirmaciones, envío único y abonos.
 *
 * Los montos que se muestran aquí son sólo una vista previa: el servidor vuelve
 * a calcular descuentos, IVA, totales y saldos antes de guardar.
 */
import Alpine from 'alpinejs';

const dinero = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

const aCentavos = (valor) => {
    const numero = Number.parseFloat(String(valor ?? '').replace(',', '.'));
    return Number.isFinite(numero) ? Math.round(numero * 100) : 0;
};

const formatear = (centavos) => dinero.format(centavos / 100);

const aNumero = (valor) => {
    const numero = Number.parseFloat(String(valor ?? '').replace(',', '.'));
    return Number.isFinite(numero) ? numero : 0;
};

/**
 * Mismo orden de cálculo que App\Services\CalculadoraComercial:
 * bruto → descuento → subtotal (base imponible) → IVA → total. Todo en centavos.
 */
const montosLinea = (cantidad, precioBase, porcentajeDescuento, porcentajeIva) => {
    const bruto = Math.round(aNumero(cantidad) * aCentavos(precioBase));
    const descuento = Math.round((bruto * aNumero(porcentajeDescuento)) / 100);
    const subtotal = bruto - descuento;
    const iva = Math.round((subtotal * aNumero(porcentajeIva)) / 100);

    return { bruto, descuento, subtotal, iva, total: subtotal + iva };
};

const normalizarPorcentaje = (valor) =>
    valor === null || valor === undefined || valor === '' ? '' : String(Number(aNumero(valor).toFixed(2)));

const normalizarPrecio = (valor) =>
    valor === null || valor === undefined || valor === '' ? '' : aNumero(valor).toFixed(2);

const normalizarTarifa = (valor) =>
    valor === null || valor === undefined || valor === '' ? '' : String(Math.round(aNumero(valor)));

let contadorLineas = 0;
const nuevaLinea = (linea = {}) => ({
    uid: ++contadorLineas,
    producto_id: String(linea.producto_id ?? ''),
    cantidad: String(linea.cantidad ?? '1'),
    precio_unitario: normalizarPrecio(linea.precio_unitario),
    porcentaje_descuento: normalizarPorcentaje(linea.porcentaje_descuento),
    porcentaje_iva: normalizarTarifa(linea.porcentaje_iva),
    actualizar_catalogo: linea.actualizar_catalogo === true || linea.actualizar_catalogo === '1' || linea.actualizar_catalogo === 1,
});

/* ---------- Producto: precio final con descuento e IVA en tiempo real ---------- */
Alpine.data('calculadoraProducto', (inicial) => ({
    precioBase: inicial.precio_base ?? '',
    descuento: inicial.porcentaje_descuento ?? '0',
    iva: String(inicial.porcentaje_iva ?? '15'),

    get montos() {
        return montosLinea(1, this.precioBase, this.descuento, this.iva);
    },

    dinero(centavos) {
        return formatear(centavos);
    },
}));

/* ---------- Cotización / factura maestro-detalle ---------- */
Alpine.data('documentoComercial', (config) => ({
    productos: config.productos ?? [],
    lineas: (config.lineas?.length ? config.lineas : [{}]).map(nuevaLinea),
    errores: config.errores ?? {},
    puedeEditarCatalogo: Boolean(config.puedeEditarCatalogo),
    tipo: config.tipo ?? null,
    emision: config.emision ?? '',
    vencimiento: config.vencimiento ?? '',
    plazo: config.plazo ?? null,

    init() {
        // Líneas que llegan sin precio, descuento o IVA toman los del catálogo.
        this.lineas.forEach((linea) => this.completarDesdeCatalogo(linea, false));
    },

    producto(id) {
        return this.productos.find((p) => String(p.id) === String(id)) ?? null;
    },

    /**
     * Copia el precio unitario, el % de descuento y el % de IVA del catálogo a la línea.
     * Con forzar = true (al cambiar de producto) reemplaza lo que hubiera.
     */
    completarDesdeCatalogo(linea, forzar = true) {
        const p = this.producto(linea.producto_id);

        if (!p) {
            if (forzar) {
                linea.precio_unitario = '';
                linea.porcentaje_descuento = '';
                linea.porcentaje_iva = '';
                linea.actualizar_catalogo = false;
            }
            return;
        }

        if (forzar || linea.precio_unitario === '') {
            linea.precio_unitario = normalizarPrecio(p.precio_base);
        }
        if (forzar || linea.porcentaje_descuento === '') {
            linea.porcentaje_descuento = normalizarPorcentaje(p.porcentaje_descuento);
        }
        if (forzar || linea.porcentaje_iva === '') {
            linea.porcentaje_iva = normalizarTarifa(p.porcentaje_iva);
        }
        if (forzar) {
            linea.actualizar_catalogo = false;
        }
    },

    montos(linea) {
        const p = this.producto(linea.producto_id);

        return p
            ? montosLinea(
                linea.cantidad,
                linea.precio_unitario === '' ? p.precio_base : linea.precio_unitario,
                linea.porcentaje_descuento === '' ? p.porcentaje_descuento : linea.porcentaje_descuento,
                linea.porcentaje_iva === '' ? p.porcentaje_iva : linea.porcentaje_iva,
            )
            : { bruto: 0, descuento: 0, subtotal: 0, iva: 0, total: 0 };
    },

    get totales() {
        return this.lineas.reduce(
            (suma, linea) => {
                const m = this.montos(linea);
                return {
                    bruto: suma.bruto + m.bruto,
                    descuento: suma.descuento + m.descuento,
                    subtotal: suma.subtotal + m.subtotal,
                    iva: suma.iva + m.iva,
                    total: suma.total + m.total,
                };
            },
            { bruto: 0, descuento: 0, subtotal: 0, iva: 0, total: 0 },
        );
    },

    precioCatalogo(linea) {
        const p = this.producto(linea.producto_id);
        return p ? formatear(aCentavos(p.precio_base)) : '—';
    },

    precioManual(linea) {
        const p = this.producto(linea.producto_id);
        return Boolean(p) && linea.precio_unitario !== '' && aCentavos(linea.precio_unitario) !== aCentavos(p.precio_base);
    },

    descuentoManual(linea) {
        const p = this.producto(linea.producto_id);
        return Boolean(p) && linea.porcentaje_descuento !== '' && aNumero(linea.porcentaje_descuento) !== aNumero(p.porcentaje_descuento);
    },

    ivaManual(linea) {
        const p = this.producto(linea.producto_id);
        return Boolean(p) && linea.porcentaje_iva !== '' && aNumero(linea.porcentaje_iva) !== aNumero(p.porcentaje_iva);
    },

    difiereDelCatalogo(linea) {
        return this.precioManual(linea) || this.descuentoManual(linea) || this.ivaManual(linea);
    },

    get lineasValidas() {
        return this.lineas.filter((l) => this.producto(l.producto_id) && aNumero(l.cantidad) > 0).length;
    },

    usadoEnOtraLinea(productoId, indice) {
        return this.lineas.some((l, i) => i !== indice && String(l.producto_id) === String(productoId));
    },

    agregar() {
        this.lineas.push(nuevaLinea());
        this.$nextTick(() => {
            const selectores = this.$root.querySelectorAll('[data-selector-producto]');
            selectores[selectores.length - 1]?.focus();
        });
    },

    quitar(indice) {
        if (this.lineas.length === 1) {
            this.lineas = [nuevaLinea()];
            return;
        }
        this.lineas.splice(indice, 1);
    },

    error(indice, campo) {
        return this.errores[`lineas.${indice}.${campo}`] ?? '';
    },

    aplicarPlazo(dias) {
        if (!this.emision) {
            return;
        }
        const fecha = new Date(`${this.emision}T12:00:00`);
        fecha.setDate(fecha.getDate() + Number(dias));
        this.vencimiento = fecha.toISOString().slice(0, 10);
        this.plazo = Number(dias);
    },

    dinero(centavos) {
        return formatear(centavos);
    },
}));

/* ---------- Menú lateral: desplegable (franja de íconos) o fijo ---------- */
const CLAVE_MENU = 'privileg.menu.fijo';

const leerPreferenciaMenu = () => {
    try {
        return window.localStorage.getItem(CLAVE_MENU) === '1';
    } catch {
        return false;
    }
};

Alpine.data('menuLateral', () => ({
    fijo: leerPreferenciaMenu(),
    sobre: false,
    conFoco: false,
    temporizador: null,

    get expandido() {
        return this.fijo || this.sobre || this.conFoco;
    },

    entrar() {
        clearTimeout(this.temporizador);
        this.sobre = true;
    },

    salir() {
        // Pequeña espera para que el menú no "parpadee" al rozar el borde.
        clearTimeout(this.temporizador);
        this.temporizador = setTimeout(() => {
            this.sobre = false;
        }, 180);
    },

    alEnfocar() {
        this.conFoco = true;
    },

    alDesenfocar(evento, contenedor) {
        // Sigue abierto mientras el foco se mueva dentro del propio menú.
        this.conFoco = contenedor.contains(evento.relatedTarget);
    },

    alternarFijo() {
        this.fijo = !this.fijo;
        try {
            window.localStorage.setItem(CLAVE_MENU, this.fijo ? '1' : '0');
        } catch {
            // Sin almacenamiento disponible: la preferencia dura hasta recargar.
        }
    },
}));

/* ---------- Reportes: vista previa antes de descargar ---------- */
Alpine.data('vistaPreviaReporte', (urls) => ({
    urls,
    tipo: null,
    titulo: '',
    formato: 'pdf',
    cargando: false,

    /** Botón "Vista previa": el formulario carga el reporte (en PDF) en el marco y se abre la ventana. */
    abrir(tipo, titulo) {
        this.formato = 'pdf';
        this.seleccionar(tipo, titulo);
        // Se abre después de que el formulario empiece a cargar la vista previa en el marco.
        setTimeout(() => this.$refs.dialogo.showModal(), 0);
    },

    /** Pestañas de la ventana: cada una vuelve a enviar los filtros con su propio reporte. */
    seleccionar(tipo, titulo) {
        this.tipo = tipo;
        this.titulo = titulo;
        this.cargando = true;
    },

    /** PDF | Excel: muestra el mismo reporte como quedará en ese formato. */
    cambiarFormato(formato) {
        this.formato = formato;
        this.cargando = true;
    },

    cerrar() {
        this.$refs.dialogo.close();
    },

    alCerrar() {
        // Al reabrir no debe verse por un instante el reporte anterior.
        this.$refs.marco.setAttribute('src', 'about:blank');
    },
}));

/* ---------- Tema claro / oscuro ---------- */
const COOKIE_TEMA = 'privileg_tema';

Alpine.data('selectorTema', () => ({
    claro: document.documentElement.dataset.tema === 'claro',

    alternar() {
        this.claro = !this.claro;
        const tema = this.claro ? 'claro' : 'oscuro';
        const seguro = window.location.protocol === 'https:' ? '; Secure' : '';

        document.documentElement.dataset.tema = tema;
        document.querySelector('meta[name="color-scheme"]')?.setAttribute('content', this.claro ? 'light' : 'dark');
        // Un año; el servidor la lee para pintar la página ya con el tema elegido.
        document.cookie = `${COOKIE_TEMA}=${tema}; Path=/; Max-Age=31536000; SameSite=Lax${seguro}`;
    },
}));

window.Alpine = Alpine;
Alpine.start();

/* ---------- Menú lateral en móviles ---------- */
function iniciarMenuLateral() {
    const menu = document.getElementById('menu-lateral');
    const fondo = document.getElementById('menu-fondo');
    const botones = document.querySelectorAll('[data-menu-toggle]');

    if (!menu || botones.length === 0) {
        return;
    }

    // En móvil el menú cerrado queda fuera de pantalla: se marca inert para que Tab no entre en él.
    const movil = window.matchMedia('(max-width: 1023.98px)');
    const sincronizarInert = () => {
        menu.inert = movil.matches && menu.dataset.abierto !== 'true';
    };

    const cambiar = (abrir) => {
        menu.dataset.abierto = abrir ? 'true' : 'false';
        fondo?.toggleAttribute('hidden', !abrir);
        botones.forEach((b) => b.setAttribute('aria-expanded', abrir ? 'true' : 'false'));
        sincronizarInert();
        if (abrir) {
            menu.querySelector('a, button')?.focus();
        }
    };

    movil.addEventListener('change', sincronizarInert);
    sincronizarInert();

    botones.forEach((b) => b.addEventListener('click', () => cambiar(menu.dataset.abierto !== 'true')));
    fondo?.addEventListener('click', () => cambiar(false));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.dataset.abierto === 'true') {
            cambiar(false);
        }
    });
}

/* ---------- Confirmación de acciones destructivas ---------- */
function iniciarConfirmaciones() {
    const dialogo = document.getElementById('dialogo-confirmacion');

    if (!dialogo) {
        return;
    }

    const mensaje = dialogo.querySelector('[data-confirm-mensaje]');
    const aceptar = dialogo.querySelector('[data-confirm-aceptar]');
    let formularioPendiente = null;

    document.addEventListener('submit', (evento) => {
        const formulario = evento.target;

        if (!(formulario instanceof HTMLFormElement) || !formulario.dataset.confirm || formulario.dataset.confirmado === 'true') {
            return;
        }

        evento.preventDefault();
        formularioPendiente = formulario;
        mensaje.textContent = formulario.dataset.confirm;
        aceptar.textContent = formulario.dataset.confirmBoton || 'Confirmar';
        dialogo.showModal();
    });

    dialogo.addEventListener('close', () => {
        if (dialogo.returnValue === 'aceptar' && formularioPendiente) {
            formularioPendiente.dataset.confirmado = 'true';
            formularioPendiente.requestSubmit();
        }
        formularioPendiente = null;
    });
}

/* ---------- Evita doble envío de formularios ---------- */
function iniciarEnvioUnico() {
    document.addEventListener('submit', (evento) => {
        const formulario = evento.target;

        if (evento.defaultPrevented || !(formulario instanceof HTMLFormElement)) {
            return;
        }

        const boton = formulario.querySelector('[data-enviando]');

        if (boton) {
            boton.setAttribute('aria-busy', 'true');
            boton.dataset.textoOriginal = boton.textContent;
            boton.textContent = boton.dataset.enviando;
            setTimeout(() => boton.setAttribute('disabled', ''), 0);
        }
    });
}

/* ---------- Formulario de abono: saldo de la factura seleccionada ---------- */
function iniciarFormularioAbono() {
    const formulario = document.querySelector('[data-abono-form]');

    if (!formulario) {
        return;
    }

    const selector = formulario.querySelector('[name="factura_id"]');
    const monto = formulario.querySelector('[name="monto_abonado"]');
    const panel = formulario.querySelector('[data-panel-factura]');
    const botonSaldo = formulario.querySelector('[data-pagar-saldo]');
    const metodo = formulario.querySelector('[name="metodo_pago"]');
    const referencia = formulario.querySelector('[name="comprobante_referencia"]');
    const restante = formulario.querySelector('[data-salida-restante]');

    const opcionActual = () => selector.selectedOptions[0];

    const actualizarRestante = () => {
        const opcion = opcionActual();
        if (!opcion?.value || !restante) {
            return;
        }
        const saldo = aCentavos(opcion.dataset.saldo);
        const resultado = saldo - aCentavos(monto.value);
        restante.textContent = resultado < 0 ? 'Supera el saldo' : formatear(resultado);
        restante.classList.toggle('text-cobre', resultado < 0);
    };

    const mostrar = () => {
        const opcion = opcionActual();
        const hayFactura = Boolean(opcion?.value);
        panel?.toggleAttribute('hidden', !hayFactura);

        if (!hayFactura) {
            return;
        }

        panel?.querySelectorAll('[data-dato]').forEach((nodo) => {
            const clave = nodo.dataset.dato;
            const valor = opcion.dataset[clave] ?? '';
            nodo.textContent = ['saldo', 'total', 'abonado'].includes(clave) ? formatear(aCentavos(valor)) : valor;
        });
        monto.max = opcion.dataset.saldo;
        actualizarRestante();
    };

    const exigirReferencia = () => {
        if (!referencia) {
            return;
        }
        const requerido = metodo.value !== 'efectivo';
        referencia.required = requerido;
        formulario.querySelector('[data-referencia-requerida]')?.toggleAttribute('hidden', !requerido);
    };

    botonSaldo?.addEventListener('click', () => {
        const opcion = opcionActual();
        if (opcion?.value) {
            monto.value = opcion.dataset.saldo;
            actualizarRestante();
            monto.focus();
        }
    });

    selector.addEventListener('change', mostrar);
    monto.addEventListener('input', actualizarRestante);
    metodo?.addEventListener('change', exigirReferencia);

    mostrar();
    exigirReferencia();
}

/* ---------- Envío automático de filtros (selects) ---------- */
function iniciarFiltros() {
    document.querySelectorAll('[data-autoenvio]').forEach((campo) => {
        campo.addEventListener('change', () => campo.form?.requestSubmit());
    });
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarMenuLateral();
    iniciarConfirmaciones();
    iniciarEnvioUnico();
    iniciarFormularioAbono();
    iniciarFiltros();
});
