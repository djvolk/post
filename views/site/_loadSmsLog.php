<?php
foreach ($log as $row)
{
    echo date('H:i d.m.Y', strtotime($row['time'])).' <strong>'.$row['status'].'</strong><br>';
}
?>
