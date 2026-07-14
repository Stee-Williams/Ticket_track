<?php

namespace App\Service;

use App\Entity\Ticket;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * Génère les exports de tickets aux formats Excel (.xlsx) et PDF.
 */
class ExportService
{
    private const STATUS_LABELS = [
        Ticket::STATUS_WAITING     => 'En attente',
        Ticket::STATUS_IN_PROGRESS => 'En cours',
        Ticket::STATUS_DONE        => 'Traité',
    ];

    private const HEADERS = [
        'Référence', 'Titre', 'Catégorie', 'Priorité', 'Statut',
        'Demandeur', 'Technicien', 'Créé le', 'Résolu le',
    ];

    /**
     * @param Ticket[] $tickets
     */
    public function toExcel(array $tickets): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tickets');

        $sheet->fromArray(self::HEADERS, null, 'A1');

        $headerStyle = $sheet->getStyle('A1:I1');
        $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $row = 2;
        foreach ($tickets as $ticket) {
            $sheet->fromArray($this->rowData($ticket), null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = (string) ob_get_clean();

        return $this->download(
            $content,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $this->filename('xlsx')
        );
    }

    /**
     * @param Ticket[] $tickets
     */
    public function toPdf(array $tickets): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($tickets), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $this->download($dompdf->output(), 'application/pdf', $this->filename('pdf'));
    }

    /**
     * @return array<int, string>
     */
    private function rowData(Ticket $ticket): array
    {
        return [
            $ticket->getReference(),
            $ticket->getTitle(),
            $ticket->getCategory(),
            ucfirst($ticket->getPriority()),
            self::STATUS_LABELS[$ticket->getStatus()] ?? $ticket->getStatus(),
            $ticket->getCreatedBy()->getName(),
            $ticket->getAssignedTo()?->getName() ?? 'Non assigné',
            $ticket->getCreatedAt()->format('d/m/Y H:i'),
            $ticket->getResolvedAt()?->format('d/m/Y H:i') ?? '—',
        ];
    }

    /**
     * @param Ticket[] $tickets
     */
    private function buildHtml(array $tickets): string
    {
        $generatedAt = (new \DateTime())->format('d/m/Y H:i');

        $rows = '';
        foreach ($tickets as $ticket) {
            $cells = array_map(
                static fn (string $v): string => '<td>' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</td>',
                $this->rowData($ticket)
            );
            $rows .= '<tr>' . implode('', $cells) . '</tr>';
        }

        $headerCells = implode('', array_map(
            static fn (string $h): string => '<th>' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</th>',
            self::HEADERS
        ));

        $count = count($tickets);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <style>
                * { font-family: 'DejaVu Sans', sans-serif; }
                body { color: #1e293b; font-size: 11px; }
                h1 { font-size: 18px; margin: 0 0 4px; color: #2563eb; }
                .meta { color: #64748b; font-size: 10px; margin-bottom: 14px; }
                table { width: 100%; border-collapse: collapse; }
                th { background: #2563eb; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; }
                td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
                tr:nth-child(even) td { background: #f8fafc; }
            </style>
        </head>
        <body>
            <h1>Ticket-Track — Export des tickets</h1>
            <div class="meta">{$count} ticket(s) · Généré le {$generatedAt}</div>
            <table>
                <thead><tr>{$headerCells}</tr></thead>
                <tbody>{$rows}</tbody>
            </table>
        </body>
        </html>
        HTML;
    }

    private function download(string $content, string $mime, string $filename): Response
    {
        return new Response($content, Response::HTTP_OK, [
            'Content-Type'        => $mime,
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    private function filename(string $ext): string
    {
        return 'tickets_' . (new \DateTime())->format('Ymd_His') . '.' . $ext;
    }
}
