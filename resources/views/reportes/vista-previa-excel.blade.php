{{--
    Vista previa del Excel dentro del marco de Reportes: cada hoja del libro .xlsx
    que se descargaría, convertida a tabla por PhpSpreadsheet (valores ya escapados).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Vista previa del Excel</title>
    <style>
        {!! $estilos !!}
    </style>
    <style>
        html { background: #e4e8ef; }
        body { margin: 0; padding: 0 16px 24px; font-family: Calibri, Carlito, 'Segoe UI', Arial, sans-serif; color: #141824; }
        .barra { position: sticky; top: 0; z-index: 1; display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding: 12px 0 10px; background: #e4e8ef; }
        .barra p { margin: 0 8px 0 0; font-size: 12px; color: #5b6577; }
        .barra a { padding: 5px 12px; border: 1px solid #c9d1dd; border-radius: 6px; background: #fff; color: #185c37; font-size: 12px; font-weight: 600; text-decoration: none; }
        .barra a:hover { background: #e8f4ec; }
        .hoja { margin-bottom: 20px; overflow-x: auto; background: #fff; border: 1px solid #c9d1dd; border-radius: 6px; box-shadow: 0 12px 32px -18px rgb(15 23 42 / 0.35); scroll-margin-top: 56px; }
        .hoja h2 { margin: 0; padding: 7px 12px; background: #217346; color: #fff; font-size: 13px; font-weight: 600; letter-spacing: 0.2px; }
        .hoja > div { padding: 8px; }
        .hoja table { border-collapse: collapse; }
        .hoja td { border: 1px solid #e3e7ee; padding: 3px 6px; white-space: nowrap; }
    </style>
</head>
<body>
    <nav class="barra" aria-label="Hojas del libro">
        <p>{{ count($hojas) }} {{ count($hojas) === 1 ? 'hoja' : 'hojas' }} · {{ $periodo }}</p>
        @foreach ($hojas as $indice => $hoja)
            <a href="#hoja-{{ $indice }}">{{ $hoja['nombre'] }}</a>
        @endforeach
    </nav>

    @foreach ($hojas as $indice => $hoja)
        <section class="hoja" id="hoja-{{ $indice }}">
            <h2>{{ $hoja['nombre'] }}</h2>
            {!! $hoja['html'] !!}
        </section>
    @endforeach
</body>
</html>
