{{-- Campos compartidos por crear/editar cliente. Requiere $cliente. --}}
<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <x-ui.input label="RUC o cédula" name="ruc_cedula" :value="$cliente->ruc_cedula" required inputmode="numeric" maxlength="13" autocomplete="off"
        hint="10 dígitos (cédula) o 13 dígitos (RUC). Se valida el dígito verificador." />
    <x-ui.input label="Razón social" name="razon_social" :value="$cliente->razon_social" required maxlength="200" autocomplete="organization" />
    <x-ui.input label="Dirección" name="direccion" :value="$cliente->direccion" required maxlength="500" autocomplete="street-address" class="md:col-span-2" />
    <x-ui.input label="Teléfono" name="telefono" type="tel" :value="$cliente->telefono" maxlength="20" autocomplete="tel" placeholder="03 242 1234" />
    <x-ui.input label="Correo electrónico" name="correo" type="email" :value="$cliente->correo" maxlength="150" autocomplete="email" placeholder="cobranzas@cliente.com" />
</div>
