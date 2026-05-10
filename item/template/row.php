<?php $this->content = function($v) {
  $cellClass = 'border-b border-stroke px-4 py-3 text-sm text-body dark:border-strokedark dark:text-bodydark';
?>

<th scope="row" class="<?php echo $cellClass; ?> font-medium xl:pl-7">
  <a href="<?php echo './item/start/item/' . htmlspecialchars($v->item->id, ENT_QUOTES, 'UTF-8'); ?>"
     class="text-primary hover:underline">
    <?php echo htmlspecialchars((string) $v->item->id, ENT_QUOTES, 'UTF-8'); ?>
  </a>
</th>
<td class="<?php echo $cellClass; ?> break-words text-black dark:text-white">
  <?php echo htmlspecialchars((string) $v->item->name, ENT_QUOTES, 'UTF-8'); ?>
</td>
<td class="<?php echo $cellClass; ?> categoryPath"><?php echo htmlspecialchars((string) $v->iv->categoryId, ENT_QUOTES, 'UTF-8'); ?></td>
<td class="<?php echo $cellClass; ?> text-right"><?php echo number_format($v->iv->price ?? 0); ?></td>
<td class="<?php echo $cellClass; ?>">
  <?php if($v->item->pla) { ?>
    <img src="./img/pla.gif" alt="プラ" width="25" height="25">
  <?php } ?>
</td>
<td class="<?php echo $cellClass; ?>"><?php echo htmlspecialchars((string) $v->item->plaNote, ENT_QUOTES, 'UTF-8'); ?></td>
<td class="<?php echo $cellClass; ?>">
  <?php if($v->item->paper) { ?>
    <img src="./img/kami.gif" alt="紙" width="25" height="25">
  <?php } ?>
</td>
<td class="<?php echo $cellClass; ?>"><?php echo htmlspecialchars((string) $v->item->paperNote, ENT_QUOTES, 'UTF-8'); ?></td>
<td class="<?php echo $cellClass; ?> whitespace-nowrap"><?php echo $v->item->createAt->format('Y年m月d日'); ?></td>
<td class="<?php echo $cellClass; ?> whitespace-nowrap"><?php echo $v->iv->updateAt->format('Y年m月d日'); ?></td>
<td class="<?php echo $cellClass; ?>">
<?php
echo implode(', ', array_map(
    function($color) use ($v) {
        return
            '<a class="text-primary hover:underline" href="./image/start/item/' . htmlspecialchars((string)$v->item->id, ENT_QUOTES, 'UTF-8') . '/color/' . htmlspecialchars((string)$color->code, ENT_QUOTES, 'UTF-8') . '">'
        .
            htmlspecialchars((string)$color->name, ENT_QUOTES, 'UTF-8') . '(' . htmlspecialchars((string)$color->code, ENT_QUOTES, 'UTF-8') . ')'
        .
        '</a>';
    },
    iterator_to_array($v->colors),
));
?>
</td>
<td class="<?php echo $cellClass; ?>">
<?php
echo implode(', ', array_map(
    function($size) {
        return htmlspecialchars((string) $size->name, ENT_QUOTES, 'UTF-8');
    },
    iterator_to_array($v->sizes),
));
?>
</td>

<?php }; ?>
