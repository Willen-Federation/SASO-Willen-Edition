<?php $this->content = function($v) { ?>

<th scope="row" class="px-4 py-3.5 font-medium text-black dark:text-white"><a href="<?php echo './item/start/item/' . $v->item->id; ?>" class="text-primary hover:underline"><?php echo $v->item->id; ?></a></th>
<td class="px-4 py-3.5 text-gray-800 dark:text-white/90 break-words max-w-xs"><?php echo $v->item->name; ?></td>
<td class="px-4 py-3.5 text-gray-500 dark:text-gray-400 categoryPath"><?php echo $v->iv->categoryId; ?></td>
<td class="px-4 py-3.5 font-medium text-black dark:text-white"><?php echo number_format($v->iv->price??0); ?>円</td>
<td class="px-4 py-3.5">
<?php if($v->item->pla) { ?>
<img src="./img/pla.gif" alt="プラ" width="20" height="20" class="inline-block">
<?php } ?>
</td>
<td class="px-4 py-3.5 text-gray-500 dark:text-gray-400"><?php echo $v->item->plaNote; ?></td>
<td class="px-4 py-3.5">
<?php if($v->item->paper) { ?>
<img src="./img/kami.gif" alt="紙" width="20" height="20" class="inline-block">
<?php } ?>
</td>
<td class="px-4 py-3.5 text-gray-500 dark:text-gray-400"><?php echo $v->item->paperNote; ?></td>
<td class="px-4 py-3.5 text-gray-500 dark:text-gray-400 whitespace-nowrap"><?php echo $v->item->createAt->format('Y/m/d'); ?></td>
<td class="px-4 py-3.5 text-gray-500 dark:text-gray-400 whitespace-nowrap"><?php echo $v->iv->updateAt->format('Y/m/d'); ?></td>
<td class="px-4 py-3.5">
  <div class="flex flex-wrap gap-1">
    <?php
    foreach (iterator_to_array($v->colors) as $color) {
        $colorUrl = './image/start/item/' . $v->item->id . '/color/' . $color->code;
        echo '<a href="' . htmlspecialchars($colorUrl) . '" class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30 hover:bg-blue-100 transition">' . htmlspecialchars($color->name) . '</a>';
    }
    ?>
  </div>
</td>
<td class="px-4 py-3.5">
  <div class="flex flex-wrap gap-1">
    <?php
    foreach (iterator_to_array($v->sizes) as $size) {
        echo '<span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">' . htmlspecialchars($size->name) . '</span>';
    }
    ?>
  </div>
</td>

<?php }; ?>
