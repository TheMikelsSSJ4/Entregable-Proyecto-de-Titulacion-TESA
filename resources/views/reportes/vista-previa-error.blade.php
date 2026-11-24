{{-- Se muestra dentro del marco de vista previa cuando los filtros no son válidos. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Vista previa no disponible</title>
    <style>
        html { background: #e4e8ef; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; box-sizing: border-box; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #141824; }
        .caja { max-width: 30rem; background: #fff; border: 1px solid #dfe3ea; border-radius: 12px; padding: 24px 28px; box-shadow: 0 12px 32px -16px rgb(15 23 42 / 0.35); }
        h1 { margin: 0 0 6px; font-size: 17px; }
        p { margin: 0; color: #5b6577; font-size: 14px; }
        ul { margin: 12px 0 0; padding-left: 18px; }
        li { color: #c8401b; font-size: 14px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="caja" role="alert">
        <h1>No se puede mostrar la vista previa</h1>
        <p>Corrija los filtros del reporte y vuelva a intentarlo:</p>
        <ul>
            @foreach ($errores as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</body>
</html>
