<?php $this->content = function($v) { ?>

<nav aria-label="Page navigation">
<ul class="pagination justify-content-center">
<?php for($i = 1; $i <= $v->pageAmount; $i++){
  $isActive = ($i == $v->page || $v->page == null && $i == 1);
?>
<li class="page-item <?php if($isActive){ echo 'active'; } ?>">
<a class="page-link" href="<?php echo htmlspecialchars('./'.$v->request.'/sortby/'.$v->sortBy.'/direction/'.$v->direction.$v->search.'/page/'.$i, ENT_QUOTES, 'UTF-8'); ?>"
   <?php if($isActive){ echo 'aria-current="page"'; } ?>>
<?php echo $i; ?>
</a>
</li>
<?php } ?>
</ul>
</nav>

<?php }; ?>
