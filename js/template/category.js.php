//<?php $this->content = function($v) { ?>

export default class Category {
    constructor(csrftoken)
    {
        this.csrftoken = csrftoken;
        if(!document.querySelector('#categoriesRoot')??false) return;
        let appendingParent = document.querySelector('#appendingParent');
        appendingParent.addEventListener('click', e=>{
            this.showChildren(e);
            this.showFormToAdd(e);
            e.preventDefault();
        });
        this.showChildren();
    }
    showChildren = e=>{
        fetch('./category/list.json', {
            method: 'POST',
            body: JSON.stringify({
                id: e?.target.getAttribute('title')??document.querySelector('#categoryId')?.value??'',
                csrftoken: this.csrftoken
            })
        })
        .then(res=>res.json())
        .then(data=>{
            let old = document.querySelector('#parents');
            if(old) {
                document.querySelector('#categoriesRoot').removeChild(old);
            }
            let parents = document.createElement('ul');
            parents.setAttribute('id', 'parents');
            parents.setAttribute('class', 'category-list');
            this.nestChildren(data.children, parents);
            document.querySelector('#categoriesRoot').appendChild(parents);
            if(data.selected??false) {
                let selected = document.querySelector('#content'+data.selected);
                selected.classList.add('category-item--selected');
                let appendingButton = document.createElement('button');
                appendingButton.setAttribute('id', 'appendingButton');
                appendingButton.setAttribute('title', data.selected);
                appendingButton.setAttribute('class', 'inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-white text-xs font-bold hover:bg-brand-600 ml-2 shrink-0');
                appendingButton.textContent = '+';
                selected.appendChild(appendingButton);
                let editButton = document.createElement('button');
                editButton.setAttribute('id', 'editButton');
                editButton.setAttribute('title', data.selected);
                editButton.setAttribute('class', 'inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 ml-1 shrink-0');
                let editSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                editSvg.setAttribute('class', 'h-3.5 w-3.5 pointer-events-none');
                editSvg.setAttribute('viewBox', '0 0 24 24');
                editSvg.setAttribute('fill', 'none');
                editSvg.setAttribute('stroke', 'currentColor');
                editSvg.setAttribute('stroke-width', '1.5');
                let editPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                editPath.setAttribute('stroke-linecap', 'round');
                editPath.setAttribute('stroke-linejoin', 'round');
                editPath.setAttribute('d', 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10');
                editSvg.appendChild(editPath);
                editButton.appendChild(editSvg);
                selected.appendChild(editButton);
                let deleteButton = document.createElement('button');
                deleteButton.setAttribute('id', 'deleteButton');
                deleteButton.setAttribute('title', data.selected);
                deleteButton.setAttribute('class', 'inline-flex h-6 w-6 items-center justify-center rounded-full bg-error-100 dark:bg-error-900/30 text-error-600 dark:text-error-400 hover:bg-error-200 dark:hover:bg-error-900/60 ml-1 text-xs font-bold shrink-0');
                deleteButton.textContent = '−';
                selected.appendChild(deleteButton);
                appendingButton.addEventListener('click', e=>{
                    this.showFormToAdd(e);
                    e.stopPropagation();
                    e.preventDefault();
                });
                editButton.addEventListener('click', e=>{
                    this.showFormToEdit(e);
                    e.stopPropagation();
                    e.preventDefault();
                });
                deleteButton.addEventListener('click', e=>{
                    this.showFormToDelete(e);
                    e.stopPropagation();
                    e.preventDefault();
                });
                if(document.querySelector('.categoryPath')) {
                    fetch('./category/path.json/id/'+document.querySelector('#categoryId')?.getAttribute('value'), {
                        method: 'GET',
                    })
                    .then(res=>res.json())
                    .then(data=>{
                        document.querySelectorAll('.categoryPathChangable').forEach(elm=>{
                            elm.textContent = data.path;
                        });
                        let deselect = document.querySelector('#deselectCategory');
                        deselect.classList.remove('hidden');
                        deselect.addEventListener('click', e=>{
                            document.querySelector('#categoryId').setAttribute('value', '');
                            this.showChildren(e);
                            document.querySelectorAll('.categoryPathChangable').forEach(elm=>{
                                elm.textContent = '';
                            })
                            e.target.classList.add('hidden');
                        });
                    });
                }
            }
        });
    }
    nestChildren = (children, parents)=>{
        children.forEach(aParent=>{
            let content = document.createElement('p');
            content.setAttribute('title', aParent.key);
            content.setAttribute('class', 'category-item__text');
            content.textContent = aParent.name;
            let div = document.createElement('div');
            div.setAttribute('id', 'content'+aParent.key);
            div.setAttribute('title', aParent.key);
            div.setAttribute('class', 'category-item');
            div.appendChild(content);
            let li = document.createElement('li');
            li.appendChild(div);
            let children = document.createElement('ul');
            children.setAttribute('id', 'childrenOf'+aParent.key);
            children.setAttribute('class', 'category-children');
            this.nestChildren(aParent.children, children);
            li.appendChild(children);
            parents.appendChild(li);
            div.addEventListener('click', e=>{
                this.showChildren(e);
                document.querySelector('#categoryId')?.setAttribute('value', aParent.key);
            });
        });
    }
    showFormToAdd = e=>{
        let selected = e.target.getAttribute('title');
        let form = document.querySelector('#newCategoryForm');
        if(form) {
            let aParent = form.parentNode;
            aParent.removeChild(form);
        }
        this.addForm(selected)
    }
    addForm = selected=>{
        let newCategory = document.createElement('input');
        newCategory.setAttribute('id', 'newCategory');
        newCategory.setAttribute('type', 'text');
        newCategory.setAttribute('name', 'categoryName');
        newCategory.setAttribute('required', '');
        newCategory.setAttribute('maxlength', '50');
        newCategory.setAttribute('class', 'form-input w-full');
        let parentId = document.createElement('input');
        parentId.setAttribute('type', 'hidden');
        parentId.setAttribute('name', 'parentId');
        parentId.setAttribute('value', selected??'');
        let submit = document.createElement('button');
        submit.setAttribute('id', 'newCategorySubmit');
        submit.setAttribute('type', 'submit');
        submit.setAttribute('class', 'btn btn-sm btn-primary');
        submit.textContent = '登録';
        let form = document.createElement('form');
        form.setAttribute('id', 'newCategoryForm');
        form.setAttribute('title', selected??'');
        form.setAttribute('class', 'mt-3 flex flex-col gap-2');
        form.appendChild(newCategory);
        form.appendChild(parentId);
        form.appendChild(submit);
        let div = document.querySelector(selected?'#childrenOf'+selected:'#appendingParentInputs');
        div.appendChild(form);
        form.addEventListener('submit', e=>{
            fetch('./category/add', {
                method: 'POST',
                body: JSON.stringify({
                    categoryName: e.target.categoryName.value,
                    id: e.target.parentId.value,
                    csrftoken: this.csrftoken
                })
            })
            .then(res=>{});
            if(e.target.parentId.value === '') {
                div.removeChild(form);
            }
            this.showChildren(e);
            e.preventDefault();
        });
    }
    showFormToEdit = e=>{
        let selected = e.target.getAttribute('title');
        let form = document.querySelector('#newCategoryForm');
        if(form) {
            let aParent = form.parentNode;
            aParent.removeChild(form);
        }
        this.editForm(selected);
    }
    editForm = selected=>{
        let editCategory = document.createElement('input');
        editCategory.setAttribute('id', 'editCategory');
        editCategory.setAttribute('type', 'text');
        editCategory.setAttribute('required', '');
        editCategory.setAttribute('name', 'categoryName');
        editCategory.setAttribute('maxlength', '50');
        editCategory.setAttribute('class', 'form-input w-full');
        editCategory.setAttribute('value', document.querySelector('#content'+selected).firstChild.textContent);
        let selfId = document.createElement('input');
        selfId.setAttribute('type', 'hidden');
        selfId.setAttribute('name', 'selfId');
        selfId.setAttribute('value', selected);
        let submit = document.createElement('button');
        submit.setAttribute('id', 'editCategorySubmit');
        submit.setAttribute('type', 'submit');
        submit.setAttribute('class', 'btn btn-sm btn-primary');
        submit.textContent = '変更';
        let form = document.createElement('form');
        form.setAttribute('id', 'newCategoryForm');
        form.setAttribute('title', selected);
        form.setAttribute('class', 'mt-3 flex flex-col gap-2');
        form.appendChild(editCategory);
        form.appendChild(selfId);
        form.appendChild(submit);
        let div = document.querySelector('#content'+selected);
        div.appendChild(form);
        form.addEventListener('submit', e=>{
            fetch('./category/replace', {
                method: 'POST',
                body: JSON.stringify({
                    categoryName: e.target.categoryName.value,
                    id: e.target.selfId.value,
                    csrftoken: this.csrftoken
                })
            })
            .then(res=>{});
            this.showChildren(e);
            e.preventDefault();
        });
        form.addEventListener('click', e=>{
            e.stopPropagation();
        });
    }
    showFormToDelete = e=>{
        let selected = e.target.getAttribute('title');
        let form = document.querySelector('#newCategoryForm');
        if(form) {
            let aParent = form.parentNode;
            aParent.removeChild(form);
        }
        this.deleteForm(selected);
    }
    deleteForm = selected=>{
        let deleteMethod = (value, name)=>{
            let div = document.createElement('div');
            div.setAttribute('class', 'flex items-center gap-2');
            let radio = document.createElement('input');
            radio.setAttribute('id', value);
            radio.setAttribute('type', 'radio');
            radio.setAttribute('name', 'method');
            radio.setAttribute('value', value);
            radio.setAttribute('required', '');
            let label = document.createElement('label');
            label.setAttribute('for', value);
            label.setAttribute('class', 'text-sm text-gray-700 dark:text-gray-300 cursor-pointer');
            label.textContent = name;
            div.appendChild(radio);
            div.appendChild(label);
            return div;
        }
        let childrenPromote = deleteMethod('childrenPromote', '単一(子孫分類を一段階昇格)');
        let withChildren = deleteMethod('withChildren', '連座(子孫分類ごと全て削除)');
        let selfId = document.createElement('input');
        selfId.setAttribute('type', 'hidden');
        selfId.setAttribute('name', 'selfId');
        selfId.setAttribute('value', selected);
        let submit = document.createElement('button');
        submit.setAttribute('id', 'deleteCategorySubmit');
        submit.setAttribute('type', 'submit');
        submit.setAttribute('class', 'btn btn-sm bg-error-500 text-white hover:bg-error-600 mt-1');
        submit.textContent = '削除';
        let form = document.createElement('form');
        form.setAttribute('id', 'newCategoryForm');
        form.setAttribute('title', selected);
        form.setAttribute('class', 'mt-3 flex flex-col gap-2');
        form.appendChild(childrenPromote);
        form.appendChild(withChildren);
        form.appendChild(selfId);
        form.appendChild(submit);
        let div = document.querySelector('#content'+selected);
        div.appendChild(form);
        form.addEventListener('submit', e=>{
            fetch('./category/delete', {
                method: 'POST',
                body: JSON.stringify({
                    method: e.target.method.value,
                    id: e.target.selfId.value,
                    csrftoken: this.csrftoken
                })
            })
            .then(res=>{})
            .then(data=>{
                document.querySelector('#deselectCategory')?.dispatchEvent(new Event('click'));
                this.showChildren();
            });
            e.preventDefault();
        });
        form.addEventListener('click', e=>{
            e.stopPropagation();
        });
    }
}

//<?php } ?>
