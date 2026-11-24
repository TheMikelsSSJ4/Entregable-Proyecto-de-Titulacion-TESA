{{-- Banda superior común. Variables: $empresa (EmpresaConfiguracion), $tipoDocumento, $numero, $detalle (opcional) --}}
<table class="banda">
    <tr>
        <td style="width: 60%;">
            <p class="marca">{{ \Illuminate\Support\Str::upper($empresa->razon_social) }}<span>·</span></p>
            <p class="sub" style="margin-top: 4px;">{{ $empresa->lineaContacto() }}</p>
        </td>
        <td style="width: 40%; text-align: right;">
            <p class="doc-tipo">{{ $tipoDocumento }}</p>
            <p class="doc-num">{{ $numero }}</p>
            @isset($detalle)<p class="sub" style="margin-top: 3px;">{{ $detalle }}</p>@endisset
        </td>
    </tr>
</table>
