<?php

namespace App\Services;

use Maatwebsite\Excel\Excel as FormatoExcel;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html;

/**
 * Vista previa en pantalla de un reporte de Excel: genera el mismo .xlsx que se
 * descargaría y lo convierte a tablas HTML, una por hoja, con sus formatos
 * (moneda, porcentaje, anchos de columna y la cabecera de la empresa).
 */
class VistaPreviaExcel
{
    /**
     * @return array{estilos: string, hojas: list<array{nombre: string, html: string}>}
     */
    public function hojas(object $exportacion): array
    {
        $archivo = tempnam(sys_get_temp_dir(), 'vista-excel-');

        try {
            file_put_contents($archivo, Excel::raw($exportacion, FormatoExcel::XLSX));
            $libro = IOFactory::load($archivo);
        } finally {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }

        $escritor = new Html($libro);
        $escritor->writeAllSheets();
        $estilos = $escritor->generateStyles(false);

        $hojas = [];

        foreach ($libro->getAllSheets() as $indice => $hoja) {
            $escritor->setSheetIndex($indice);
            $hojas[] = ['nombre' => $hoja->getTitle(), 'html' => $escritor->generateSheetData()];
        }

        return ['estilos' => $estilos, 'hojas' => $hojas];
    }
}
