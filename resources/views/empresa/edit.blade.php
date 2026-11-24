<x-layouts.app title="Configuración de empresa" heading="Configuración de la empresa">
    <x-ui.page-header eyebrow="Administrador General" tone="violeta"
        title="Datos de facturación de la empresa"
        description="Se imprimen automáticamente en la cabecera de todos los PDFs (facturas, cotizaciones, reportes) y de los archivos Excel." />

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <form method="POST" action="{{ route('empresa.update') }}" class="card flex flex-col gap-6 p-5 sm:p-6" novalidate>
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-ui.input label="Razón social" name="razon_social" :value="$empresa->razon_social" required maxlength="200" autocomplete="organization" class="md:col-span-2" />
                <x-ui.input label="RUC" name="ruc" :value="$empresa->ruc" required inputmode="numeric" maxlength="13" class="tnum" hint="13 dígitos. Se valida su estructura." />
                <x-ui.input label="Teléfono" name="telefono" type="tel" :value="$empresa->telefono" maxlength="20" autocomplete="tel" />
                <x-ui.input label="Dirección" name="direccion" :value="$empresa->direccion" required maxlength="500" autocomplete="street-address" class="md:col-span-2" />
                <x-ui.input label="Correo de facturación" name="correo" type="email" :value="$empresa->correo" maxlength="150" autocomplete="email" class="md:col-span-2" />
            </div>
            <div class="flex justify-end border-t border-tinta/[0.06] pt-5">
                <x-ui.button type="submit" icon="check" data-enviando="Guardando…">Guardar datos de la empresa</x-ui.button>
            </div>
        </form>

        <aside class="card flex flex-col gap-3 self-start p-5" aria-labelledby="titulo-vista">
            <p id="titulo-vista" class="eyebrow text-cian">Así aparece en los documentos</p>
            <div class="rounded-xl bg-obsidiana p-4">
                <p class="font-display text-sm font-bold tracking-wide">{{ \Illuminate\Support\Str::upper($empresa->razon_social) }}</p>
                <p class="mt-1 text-xs break-words text-niebla">{{ $empresa->lineaContacto() }}</p>
                <p class="eyebrow mt-3 text-[0.6rem] text-menta">Factura · Crédito</p>
                <p class="tnum font-display text-sm font-semibold">N.º 001-001-000000123</p>
            </div>
            <p class="text-xs text-niebla">Los documentos ya descargados no cambian; los nuevos usan estos datos.</p>
        </aside>
    </div>
</x-layouts.app>
