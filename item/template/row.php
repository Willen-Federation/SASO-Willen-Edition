<?php $this->content = function($v) { ?>

<th scope="row"><a href="<?php echo './item/start/item/' . $v->item->id; ?>"><?php echo $v->item->id; ?></a></th>
<td class="text-break"><?php echo htmlspecialchars($v->item->name, ENT_QUOTES, 'UTF-8'); ?></td>
<td class="categoryPath"><?php echo htmlspecialchars((string)$v->iv->categoryId, ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo number_format($v->iv->price??0); ?></td>
<td>
<?php if($v->item->pla) { ?>
<img src="./img/pla.gif" alt="プラ" width="25" height="25">
<?php } ?>
</td>
<td><?php echo htmlspecialchars($v->item->plaNote, ENT_QUOTES, 'UTF-8'); ?></td>
<td>
<?php if($v->item->paper) { ?>
<img src="./img/kami.gif" alt="紙" width="25" height="25">
<?php } ?>
</td>
<td><?php echo htmlspecialchars($v->item->paperNote, ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo $v->item->createAt->format('Y年m月d日'); ?></td>
<td><?php echo $v->iv->updateAt->format('Y年m月d日'); ?></td>
<td>
<?php
echo implode(', ', array_map(
    function($color) use ($v) {
        return
            '<a href="./image/start/item/' . $this->item->id . '/color/' . htmlspecialchars($color->code, ENT_QUOTES, 'UTF-8') . '">'
        .
            htmlspecialchars($color->name, ENT_QUOTES, 'UTF-8') . '(' . htmlspecialchars($color->code, ENT_QUOTES, 'UTF-8') . ')'
        .
        '</a>';
    },
    iterator_to_array($v->colors),
));
?>
</td>
<td>
<?php
echo implode(', ', array_map(
    function($size) {
        return htmlspecialchars($size->name, ENT_QUOTES, 'UTF-8');
    },
    iterator_to_array($v->sizes),
));
?>
</td>

<?php }; ?>
