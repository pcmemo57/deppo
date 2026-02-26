<?php
/**
 * Yardımcı: AJAX destekli standart tablo HTML bloğunu oluşturur.
 * Her liste sayfasında aynı düzeni kullanmak için.
 */
function renderTableHeader(string $title, string $addBtn = ''): void
{
    echo '<div class="card">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h3 class="card-title">' . $title . '</h3>';
    if ($addBtn)
        echo $addBtn;
    echo '</div>';
}