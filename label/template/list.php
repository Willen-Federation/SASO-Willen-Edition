<?php $this->content = function($v) { ?>

<?php foreach ($v->labels as $label){ ?>
<li class="relative">
    <input type="radio" name="labelName" id="radio<?php echo $label->name; ?>" value="<?php echo $label->name; ?>" class="peer hidden">
    <label for="radio<?php echo $label->name; ?>" class="flex flex-col p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-indigo-500 cursor-pointer transition-all peer-checked:border-indigo-600 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 peer-checked:ring-2 peer-checked:ring-indigo-500 shadow-sm">
        <span class="text-xs font-bold text-gray-400 uppercase mb-1">ラベル型番</span>
        <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo $label->name; ?></span>
    </label>
</li>
<?php } ?>

<?php }; ?>
