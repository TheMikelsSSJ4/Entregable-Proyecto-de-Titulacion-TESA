@include('errors.layout', [
    'codigo' => 500,
    'titulo' => 'Error interno del sistema',
    'mensaje' => 'Ocurrió un problema inesperado. La operación no se completó; intente nuevamente o informe a soporte técnico.',
])
