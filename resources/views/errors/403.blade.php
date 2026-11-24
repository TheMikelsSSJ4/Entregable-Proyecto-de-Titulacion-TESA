@include('errors.layout', [
    'codigo' => 403,
    'titulo' => 'Acceso restringido',
    'mensaje' => 'Su rol no tiene permiso para esta sección o acción. Si cree que es un error, comuníquese con Gerencia.',
])
