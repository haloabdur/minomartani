<?php
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=datart29_" . date('dmY-His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
ob_end_clean();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Data Warga RT 029 RW 006</title>
    </head>
    <body>

        <?php if ($content != NULL) : ?>
            <table cellspacing=0 border=1>
                <tr>
                    <th style=min-width:50px;text-align:center;font-weight:bold>NO</th>
                    <th style=min-width:50px;text-align:center;font-weight:bold>Nama Lengkap</th>
                    <th style=min-width:50px;text-align:center;font-weight:bold>Alamat</th>
                </tr>
                <?php
                foreach ($content as $i=>$value) :
                    ?>
                    <tr>
                        <td style=min-width:50px;><?php echo ($i+1); ?></td>
                        <td style=min-width:50px;><?php echo $value->nama_warga; ?></td>
                        <td style=min-width:50px;><?php echo $value->alamat_lengkap; ?></td>
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