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
            parents.setAttribute('class', 'list-group');
            this.nestChildren(data.children, parents);
            document.querySelector('#categoriesRoot').appendChild(parents);
            if(data.selected??false) {
                let selected = document.querySelector('#content'+data.selected);
                let appendingButton = document.createElement('button');
                appendingButton.setAttribute('id', 'appendingButton');
                appendingButton.setAttribute('title', data.selected);
                appendingButton.textContent = '+';
                selected.appendChild(appendingButton);
                let editButton = document.createElement('button');
                editButton.setAttribute('id', 'editButton');
                editButton.setAttribute('title', data.selected);
                let editMark = document.createElement('i');
                editMark.setAttribute('class', 'bi bi-pencil-square');
                editMark.setAttribute('title', data.selected);
                editButton.appendChild(editMark);
                selected.appendChild(editButton);
                let deleteButton = document.createElement('button');
                deleteButton.setAttribute('id', 'deleteButton');
                deleteButton.setAttribute('title', data.selected);
                deleteButton.textContent = '-';
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
                        deselect.setAttribute('class', '');
                        deselect.addEventListener('click', e=>{
                            document.querySelector('#categoryId').setAttribute('value', '');
                            this.showChildren(e);
                            document.querySelectorAll('.categoryPathChangable').forEach(elm=>{
                                elm.textContent = '';
                            })
                            e.target.setAttribute('class', 'hidden');
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
            content.textContent = aParent.name;
            let div = document.createElement('div');
            div.setAttribute('id', 'content'+aParent.key);
            div.setAttribute('title', aParent.key);
            div.appendChild(content);
            let li = document.createElement('li');
            li.setAttribute('class', 'list-group-item list-group-item-action');
            li.appendChild(div);
            let children = document.createElement('ul');
            children.setAttribute('id', 'childrenOf'+aParent.key);
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
        newCategory.setAttribute('maxlength', '50')
        let parentId = document.createElement('input');
        parentId.setAttribute('type', 'hidden');
        parentId.setAttribute('name', 'parentId');
        parentId.setAttribute('value', selected??'');
        let submit = document.createElement('button');
        submit.setAttribute('id', 'newCategorySubmit');
        submit.setAttribute('type', 'submit');
        submit.textContent = '登録';
        let form = document.createElement('form');
        form.setAttribute('id', 'newCategoryForm');
        form.setAttribute('title', selected??'');
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
        editCategory.setAttribute('maxlength', '50')
        editCategory.setAttribute('value', document.querySelector('#content'+selected).firstChild.textContent);
        let selfId = document.createElement('input');
        selfId.setAttribute('type', 'hidden');
        selfId.setAttribute('name', 'selfId');
        selfId.setAttribute('value', selected);
        let submit = document.createElement('button');
        submit.setAttribute('id', 'editCategorySubmit');
        submit.setAttribute('type', 'submit');
        submit.textContent = '変更';
        let form = document.createElement('form');
        form.setAttribute('id', 'newCategoryForm');
        form.setAttribute('title', selected);
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
            let radio = document.createElement('input');
            radio.setAttribute('id', value);
            radio.setAttribute('type', 'radio');
            radio.setAttribute('name', 'method');
            radio.setAttribute('value', value);
            radio.setAttribute('required', '');
            let label = document.createElement('label');
            label.setAttribute('for', value);
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
        submit.textContent = '削除';
        let form = document.createElement('form');
        form.setAttribute('id', 'newCategoryForm');
        form.setAttribute('title', selected);
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
