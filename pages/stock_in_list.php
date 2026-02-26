<?php
/**
 * Stok Giriş Listesi — Tüm giriş kayıtlarını listeler
 */
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("SELECT id,name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="card-title"><i class="fas fa-clipboard-list me-2"></i>Ürün Giriş Listesi</h3>
        <a href="<?= BASE_URL?>/index.php?page=stock_in" class="btn btn-success btn-sm">
          <i class="fas fa-plus me-1"></i>Yeni Giriş
        </a>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <input type="text" id="searchBox" class="form-control form-control-sm" placeholder="Ara..." style="width:200px">
          <select id="warehouseFilter" class="form-select form-select-sm" style="width:auto">
            <option value="">Tüm Depolar</option>
            <?php foreach ($warehouses as $w): ?>
            <option value="<?= e($w['id'])?>"><?= e($w['name'])?></option>
            <?php
endforeach; ?>
          </select>
          <select id="perPage" class="form-select form-select-sm" style="width:auto">
            <option value="10">10</option><option value="25" selected>25</option><option value="50">50</option><option value="100">100</option>
          </select>
          <span id="totalCount" class="text-muted small"></span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-striped table-bordered table-sm">
            <thead class="table-dark"><tr>
              <th>#</th><th>Ürün</th><th>Depo</th><th>Tedarikçi</th><th>Adet</th><th>Birim Fiyat</th><th>EUR Fiyat</th><th>Not</th><th>Tarih</th><th style="width:70px">İşlem</th>
            </tr></thead>
            <tbody id="tableBody"></tbody>
          </table>
        </div>
        <div id="pagination" class="d-flex justify-content-center mt-2"></div>
      </div>
    </div>
  </div>
</div>

<!-- Düzenleme Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Giriş Kaydını Düzenle</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editForm">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="editId">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3"><label class="form-label">Depo</label>
                <select name="warehouse_id" id="editWarehouse" class="form-select">
                  <?php foreach ($warehouses as $w): ?><option value="<?= e($w['id'])?>"><?= e($w['name'])?></option><?php
endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3"><label class="form-label">Ürün</label>
                <select name="product_id" id="editProduct" class="form-select" style="width:100%"></select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3"><label class="form-label">Adet</label>
                <input type="number" name="quantity" id="editQty" class="form-control" min="0.001" step="any">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3"><label class="form-label">Tedarikçi</label>
                <select name="supplier_id" id="editSupplier" class="form-select" style="width:100%">
                  <option value="">— Seçin —</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3"><label class="form-label">Birim Fiyat</label>
                <input type="text" name="unit_price" id="editPrice" class="form-control price-format">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3"><label class="form-label">Para Birimi</label>
                <select name="currency" id="editCurrency" class="form-select">
                  <option value="TL">TL</option><option value="USD">USD</option><option value="EUR">EUR</option>
                </select>
              </div>
            </div>
            <div class="col-md-8">
              <div class="mb-3"><label class="form-label">Not</label>
                <input type="text" name="note" id="editNote" class="form-control">
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        <button type="button" class="btn btn-info text-white" id="btnEditSave"><i class="fas fa-save me-1"></i>Kaydet</button>
      </div>
    </div>
  </div>
</div>

<script>
var curPage=1,curPerPage=25,curSearch='',searchTimer,warehouseFilter=0;
var apiUrl='<?= BASE_URL?>/api/stock_in.php';
function esc(v){return $('<span>').text(v||'').html();}

// Edit modal Select2
$('#editProduct').select2({theme:'bootstrap-5',placeholder:'— Ürün —',width:'100%',
    ajax:{url:'<?= BASE_URL?>/api/products.php',data:function(p){return{action:'search_select2',q:p.term||''};},processResults:function(d){return{results:d.results};},delay:300},
    templateResult:function(i){if(i.loading)return i.text;var no='<?= BASE_URL?>/assets/no-image.png',img=i.image?'<?= BASE_URL?>/images/UrunResim/'+i.image:no;return $('<span><img src="'+img+'" class="select2-product-img" onerror="this.src=\''+no+'\'" > '+$('<x>').text(i.text).html()+'</span>');}
});
$.get('<?= BASE_URL?>/api/suppliers.php',{action:'active_list'},function(r){if(!r.success)return;$.each(r.data,function(i,s){$('#editSupplier').append('<option value="'+s.id+'">'+esc(s.name)+'</option>');});$('#editSupplier').select2({theme:'bootstrap-5',placeholder:'—',allowClear:true,width:'100%'});},'json');

function load(){
    $.get(apiUrl,{action:'list',page:curPage,per_page:curPerPage,search:curSearch,warehouse_id:warehouseFilter},function(r){
        if(!r.success)return;
        var html='';
        $.each(r.data,function(i,d){
            html+='<tr><td>'+d.id+'</td><td>'+esc(d.product)+'</td><td>'+esc(d.warehouse)+'</td><td>'+esc(d.supplier||'—')+'</td>';
            html+='<td>'+d.quantity+' '+esc(d.unit)+'</td>';
            html+='<td>'+formatTurkish(parseFloat(d.unit_price||0).toFixed(2))+' '+esc(d.currency)+'</td>';
            html+='<td>'+formatTurkish(parseFloat(d.price_eur||0).toFixed(4))+'</td>';
            html+='<td>'+esc(d.note||'—')+'</td><td>'+esc(d.created_at)+'</td>';
            html+='<td><button class="btn btn-xs btn-info" onclick="editRow('+d.id+')"><i class="fas fa-edit"></i></button></td>';
            html+='</tr>';
        });
        $('#tableBody').html(html||'<tr><td colspan="10" class="text-center text-muted p-3">Kayıt bulunamadı</td></tr>');
        $('#totalCount').text('Toplam: '+r.total+' kayıt');
        renderPag(r.total);
    },'json');
}
function renderPag(total){var pages=Math.ceil(total/curPerPage);if(pages<=1){$('#pagination').html('');return;}var html='<ul class="pagination pagination-sm">',s=Math.max(1,curPage-2),e=Math.min(pages,curPage+2);if(curPage>1)html+='<li class="page-item"><a class="page-link" data-p="'+(curPage-1)+'" href="#">&laquo;</a></li>';for(var p=s;p<=e;p++)html+='<li class="page-item'+(p===curPage?' active':'')+'"><a class="page-link" data-p="'+p+'" href="#">'+p+'</a></li>';if(curPage<pages)html+='<li class="page-item"><a class="page-link" data-p="'+(curPage+1)+'" href="#">&raquo;</a></li>';html+='</ul>';$('#pagination').html(html).find('a').on('click',function(e){e.preventDefault();curPage=parseInt($(this).data('p'));load();});}

function editRow(id){
    $.get(apiUrl,{action:'get',id:id},function(r){
        if(!r.success)return showError(r.message);
        var d=r.data;
        $('#editId').val(d.id);$('#editWarehouse').val(d.warehouse_id);
        // Product select2
        var opt=new Option(d.product_name,d.product_id,true,true);
        $('#editProduct').append(opt).trigger('change');
        $('#editQty').val(d.quantity);
        $('#editPrice').val(formatTurkish(parseFloat(d.unit_price||0).toFixed(2)));
        $('#editCurrency').val(d.currency);
        $('#editNote').val(d.note);
        if(d.supplier_id){$('#editSupplier').val(d.supplier_id).trigger('change');}
        $('#editModal').modal('show');
    },'json');
}
$('#btnEditSave').on('click',function(){
    $.post(apiUrl,$('#editForm').serialize(),function(r){
        if(r.success){showSuccess(r.message);$('#editModal').modal('hide');load();}else showError(r.message);
    },'json');
});
$('#searchBox').on('input',function(){clearTimeout(searchTimer);curSearch=$(this).val();searchTimer=setTimeout(function(){curPage=1;load();},400);});
$('#warehouseFilter').on('change',function(){warehouseFilter=$(this).val();curPage=1;load();});
$('#perPage').on('change',function(){curPerPage=parseInt($(this).val());curPage=1;load();});
load();
</script>
