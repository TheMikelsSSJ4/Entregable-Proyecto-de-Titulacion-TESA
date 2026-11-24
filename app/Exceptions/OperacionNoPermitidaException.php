<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Regla de negocio contable incumplida (abono mayor al saldo, anular factura con
 * abonos, facturar una cotización ya facturada...). Se muestra al usuario como
 * mensaje de error y se renderiza en bootstrap/app.php.
 */
class OperacionNoPermitidaException extends RuntimeException {}
