<style>
    @page { margin: 26px 30px 40px 30px; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #141824; line-height: 1.45; }
    h1, h2, h3, p { margin: 0; }
    .banda { background: #080a0f; color: #f8fafc; padding: 16px 18px; border-radius: 8px; }
    .banda td { vertical-align: top; }
    .marca { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
    .marca span { color: #00f5a0; }
    .sub { color: #8a93a6; font-size: 8.5px; }
    .doc-tipo { font-size: 8px; letter-spacing: 2px; color: #00f5a0; text-transform: uppercase; }
    .doc-num { font-size: 15px; font-weight: bold; }
    .eyebrow { font-size: 7.5px; letter-spacing: 1.6px; text-transform: uppercase; color: #6b7385; font-weight: bold; }
    .caja { border: 1px solid #dfe3ea; border-radius: 6px; padding: 10px 12px; }
    table { width: 100%; border-collapse: collapse; }
    .tabla th { background: #f1f3f7; color: #4a5263; font-size: 7.5px; letter-spacing: 1px; text-transform: uppercase; text-align: left; padding: 6px 8px; border-bottom: 1px solid #dfe3ea; }
    .tabla td { padding: 6px 8px; border-bottom: 1px solid #eceff4; }
    .der { text-align: right; }
    .centro { text-align: center; }
    .negrita { font-weight: bold; }
    .menta { color: #00875a; }
    .cobre { color: #d9441f; }
    .oro { color: #a87800; }
    .violeta { color: #6d3fe0; }
    .muted { color: #6b7385; }
    .total-fila td { font-size: 12px; font-weight: bold; border-top: 2px solid #141824; padding-top: 8px; }
    .chip { display: inline-block; padding: 2px 7px; border-radius: 9px; font-size: 7.5px; font-weight: bold; letter-spacing: 0.8px; text-transform: uppercase; border: 1px solid #dfe3ea; }
    .chip-menta { color: #00875a; border-color: #8fdcbf; background: #e9fbf3; }
    .chip-cobre { color: #d9441f; border-color: #f6b4a3; background: #fff1ed; }
    .chip-oro { color: #8a6300; border-color: #f0d68a; background: #fff8e3; }
    .chip-cian { color: #00788a; border-color: #8fdde8; background: #e8fbfd; }
    .chip-niebla { color: #5b6478; background: #f3f4f7; }
    .pie { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 7.5px; color: #8a93a6; border-top: 1px solid #e3e6ec; padding-top: 5px; }
    .espacio { height: 12px; }
</style>
@if (! empty($vistaPrevia))
    {{-- Vista previa en pantalla: la misma plantilla sobre una hoja A4. --}}
    <style>
        html { background: #e4e8ef; }
        body {
            width: min({{ ($orientacion ?? 'landscape') === 'portrait' ? '210mm' : '297mm' }}, calc(100% - 32px));
            min-height: calc(100vh - 32px);
            margin: 16px auto;
            padding: 26px 30px 22px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 12px 32px -16px rgb(15 23 42 / 0.35);
            display: flex;
            flex-direction: column;
        }
        .pie { position: static; order: 99; margin-top: auto; padding-top: 8px; }
        body::after { content: ''; order: 98; min-height: 18px; }
    </style>
@endif
