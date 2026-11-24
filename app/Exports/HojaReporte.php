<?php

namespace App\Exports;

use App\Models\EmpresaConfiguracion;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Hoja de Excel con la cabecera de la empresa (filas 1-4), encabezados en la
 * fila 6, montos con formato de moneda y una fila opcional de totales.
 *
 * Todo texto se escribe como cadena literal: un nombre de cliente que empiece
 * con "=" nunca se ejecuta como fórmula (prevención de inyección CSV/Excel).
 */
class HojaReporte extends DefaultValueBinder implements FromArray, WithColumnFormatting, WithColumnWidths, WithCustomStartCell, WithCustomValueBinder, WithEvents, WithHeadings, WithStrictNullComparison, WithTitle
{
    private const FILA_ENCABEZADOS = 6;

    private const FORMATO_MONEDA = '"$"#,##0.00';

    /**
     * @param  list<string>  $encabezados
     * @param  list<list<string|int|float|null>>  $filas
     * @param  list<int>  $columnasMoneda  Índices (desde 0) de columnas monetarias.
     * @param  list<string|int|float|null>|null  $totales
     * @param  array<int, string>  $formatosExtra  Formato de Excel por índice de columna (p. ej. porcentaje).
     */
    public function __construct(
        private readonly EmpresaConfiguracion $empresa,
        private readonly string $tituloReporte,
        private readonly string $periodo,
        private readonly string $nombreHoja,
        private readonly array $encabezados,
        private readonly array $filas,
        private readonly array $columnasMoneda = [],
        private readonly ?array $totales = null,
        private readonly array $formatosExtra = [],
    ) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        return $this->totales === null ? $this->filas : [...$this->filas, $this->totales];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->encabezados;
    }

    public function startCell(): string
    {
        return 'A'.self::FILA_ENCABEZADOS;
    }

    public function title(): string
    {
        return mb_substr($this->nombreHoja, 0, 31);
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        $formatos = [];

        foreach ($this->columnasMoneda as $indice) {
            $formatos[$this->letra($indice)] = self::FORMATO_MONEDA;
        }

        foreach ($this->formatosExtra as $indice => $formato) {
            $formatos[$this->letra($indice)] = $formato;
        }

        return $formatos;
    }

    /**
     * Ancho según el contenido más largo de cada columna (entre 10 y 55 caracteres).
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        $anchos = [];

        foreach ($this->encabezados as $indice => $encabezado) {
            $largo = mb_strlen($encabezado);

            foreach ($this->array() as $fila) {
                $valor = $fila[$indice] ?? '';
                $largo = max($largo, mb_strlen(is_float($valor) ? number_format($valor, 2) : (string) $valor));
            }

            $anchos[$this->letra($indice)] = min(55, max(10, $largo + 3));
        }

        return $anchos;
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento): void {
                $hoja = $evento->getSheet()->getDelegate();
                $ultima = $this->letra(count($this->encabezados) - 1);

                $cabecera = [
                    1 => $this->empresa->razon_social,
                    2 => $this->empresa->lineaContacto(),
                    3 => $this->tituloReporte,
                    4 => $this->periodo.' · Generado el '.now()->format('d/m/Y H:i'),
                ];

                foreach ($cabecera as $fila => $texto) {
                    $hoja->setCellValueExplicit("A{$fila}", $texto, DataType::TYPE_STRING);
                    $hoja->mergeCells("A{$fila}:{$ultima}{$fila}");
                }

                $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $hoja->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('5B6478');
                $hoja->getStyle('A3')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('00875A');
                $hoja->getStyle('A4')->getFont()->setSize(9)->getColor()->setRGB('5B6478');

                $rangoEncabezados = 'A'.self::FILA_ENCABEZADOS.":{$ultima}".self::FILA_ENCABEZADOS;
                $estilo = $hoja->getStyle($rangoEncabezados);
                $estilo->getFont()->setBold(true)->getColor()->setRGB('F8FAFC');
                $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('080A0F');

                if ($this->totales !== null) {
                    $filaTotales = self::FILA_ENCABEZADOS + count($this->filas) + 1;
                    $estiloTotales = $hoja->getStyle("A{$filaTotales}:{$ultima}{$filaTotales}");
                    $estiloTotales->getFont()->setBold(true);
                    $estiloTotales->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
                }

                $hoja->freezePane('A'.(self::FILA_ENCABEZADOS + 1));
            },
        ];
    }

    private function letra(int $indice): string
    {
        return Coordinate::stringFromColumnIndex($indice + 1);
    }
}
