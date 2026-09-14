<?php

use App\Models\RwModel;

$__rt      = current_rt();
$__rw      = $__rt !== null ? model(RwModel::class)->find($__rt->id_rw) : null;
$__rtLabel = trim(($__rt->nama ?? 'RT 29') . ' ' . ($__rw->nama ?? ''));

header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=datawarga_" . date('dmY-His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
ob_end_clean();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Data Warga <?= esc($__rtLabel) ?></title>
    </head>
    <body>

        <?php if ($content != NULL) : ?>
            <table cellspacing=0 border=1>
                <tr>
                    <th style=min-width:50px;text-align:center;font-weight:bold>NO</th>
                    <?php foreach ($columns as $key) : ?>
                        <th style=min-width:50px;text-align:center;font-weight:bold><?php echo esc(\App\Models\WargaModel::EXPORT_COLUMNS[$key]); ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php
                foreach ($content as $i=>$value) :
                    ?>
                    <tr>
                        <td style=min-width:50px;><?php echo ($i+1); ?></td>
                        <?php foreach ($columns as $key) : ?>
                            <?php $cellStyle = in_array($key, \App\Models\WargaModel::EXPORT_TEXT_COLUMNS, true) ? "min-width:50px;mso-number-format:'\\@';" : 'min-width:50px;'; ?>
                            <td style="<?php echo $cellStyle; ?>"><?php echo export_format_warga_field($key, $value); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php
                endforeach;
                ?>
            </table>
        <?php
        else :
            echo "<h2>Data tidak ditemukan!</h2>";
        endif;
        ?>
    </body>
</html>
