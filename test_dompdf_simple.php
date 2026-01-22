<?php
require __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$html = '<html><body><h1>Test Dompdf</h1><p>This is a simple PDF test.</p></body></html>';
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$out = $dompdf->output();
file_put_contents('/tmp/test_dompdf_simple.pdf', $out);
echo "Wrote /tmp/test_dompdf_simple.pdf (" . strlen($out) . " bytes)\n";

?>
