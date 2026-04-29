<?php $this->title = 'ラベル寸法管理'; ?>
<?php $this->content = function($v) { ?>

<div class="p-6 max-w-7xl mx-auto space-y-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">ラベル寸法一覧</h2>
            <button type="button" @click="document.getElementById('newLabelSizeForm').classList.toggle('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 text-sm rounded-lg font-bold transition-all shadow-md">
                + 新規作成
            </button>
        </div>
        <div class="p-6" id="labelSizeList">
            <ul class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <?php ($v->inside)('label', 'list'); ?>
            </ul>
            <div class="mt-6 flex gap-4 border-t pt-6">
                <button type="button" id="labelSizeDeleteDisplay" class="text-sm text-gray-500 hover:text-red-600 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    削除モード切替
                </button>
                <div id="labelSizeDeleteButton" class="hidden">
                    <button type="button" id="labelSizeDelete" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg transition-all">削除を実行</button>
                </div>
            </div>
        </div>
    </div>

    <div id="newLabelSizeForm" class="hidden bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">ラベル寸法登録</h2>
            <p class="text-sm text-gray-500 mt-1">用紙サイズはA4、単位は「mm」で入力して下さい。</p>
        </div>

        <form method="post" action="./label/add/" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ラベル名</label>
                    <input id="newLabelName" type="text" name="labelName" pattern="^[0-9a-zA-Z_\-]+$" maxlength="50" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                           placeholder="例: AONE-28332">
                    <p class="text-xs text-gray-400 mt-1">※メーカと品番等で重複しない名前（半角英数、ハイフン、アンダーバー）</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-blue-600 mb-2">幅 (mm)</label>
                    <input type="number" name="width" step="0.1" min="0" max="999.9" required
                           class="w-full rounded-lg border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3">
                </div>
                <div>
                    <label class="block text-sm font-bold text-red-600 mb-2">高さ (mm)</label>
                    <input type="number" name="height" step="0.1" min="0" max="999.9" required
                           class="w-full rounded-lg border-red-300 shadow-sm focus:border-red-500 focus:ring-red-500 py-2 px-3">
                </div>

                <div class="grid grid-cols-2 gap-4 md:col-span-2">
                    <div>
                        <label class="block text-sm font-bold text-emerald-600 mb-2">左余白 (mm)</label>
                        <input type="number" name="marginLeft" step="0.1" min="0" max="999.9" required
                               class="w-full rounded-lg border-emerald-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 py-2 px-3">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-emerald-600 mb-2">上余白 (mm)</label>
                        <input type="number" name="marginTop" step="0.1" min="0" max="999.9" required
                               class="w-full rounded-lg border-emerald-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 py-2 px-3">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-emerald-600 mb-2">横間隔 (mm)</label>
                        <input type="number" name="intervalColumn" step="0.1" min="0" max="999.9" required
                               class="w-full rounded-lg border-emerald-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 py-2 px-3">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-emerald-600 mb-2">縦間隔 (mm)</label>
                        <input type="number" name="intervalRow" step="0.1" min="0" max="999.9" required
                               class="w-full rounded-lg border-emerald-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 py-2 px-3">
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <input id="newLabelSizeSubmit" type="submit" value="寸法を登録"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-12 rounded-xl shadow-lg transition-all cursor-pointer">
            </div>
        </form>
    </div>

    <div class="bg-gray-100 dark:bg-gray-900/50 p-8 rounded-[2.5rem] border-4 border-dashed border-gray-200 dark:border-gray-800 flex justify-center shadow-inner">
        <?php ($v->inside)('label', 'svg'); ?>
    </div>
</div>
<?php }; ?>
