<?php
use sb\prettydumper\Dumper;
// echo Dumper::dump($OperationHistory);
?>
<div class="alert alert-warning" role="alert">
    Контрольны срок доставки <strong><?= $delivery_period ?> дней</strong>
</div>

<?php if (!empty($model->delivery_lateness)) : ?>
    <div class="alert alert-danger" role="alert">
        Дней опоздания <strong><?= $model->delivery_lateness ?></strong>
    </div>
<?php endif; ?>

<table class="table table-bordered text-center" style="background-color: transparent;">
    <thead>
        <tr>
            <th class="text-center" rowspan="2">Операция</th>
            <th class="text-center" rowspan="2">Дата</th>
            <th class="text-center" rowspan="2">Атрибут операции</th>
            <th class="text-center" colspan="2">Место проведения операции</th>
            <th class="text-center" colspan="2">Адресовано</th>
        </tr>
        <tr>
            <th class="text-center">Индекс</th>
            <th class="text-center">Название ОПС</th>
            <th class="text-center">Индекс</th>
            <th class="text-center">Адрес</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($OperationHistory as $history): ?>
            <tr>
                <td><?= $history->operationType ?></td>
                <td><?= date("d-m-Y H:i:s", strtotime($history->operationDate)) ?></td>
                <td><?= $history->operationAttribute ?></td>
                <td><?= $history->operationPlacePostalCode ?></td>
                <td><?= $history->operationPlaceName ?></td>
                <td><?= $history->destinationPostalCode ?></td>
                <td><?= $history->destinationAddress ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
