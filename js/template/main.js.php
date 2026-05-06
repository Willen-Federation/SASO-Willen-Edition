//<?php $this->content = function($v) { ?>

import Password from './password.js';
import Label from './label.js';
import Category from './category.js';
import Item from './item.js';
import ItemEdit from './itemEdit.js';
import List from './list.js';
import Feature from './feature.js';
import Barcode from './barcode.js';
import CSRF from './csrf.js';
import Shelf from './shelf.js';
import ArchiveAll from './archiveAll.js';

let csrftoken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let initialize = callback=>{
    try {
        callback();
    } catch (error) {
        console.error('SASO client initializer failed:', error);
    }
};

document.documentElement?.setAttribute('data-saso-main-loaded', '1');

initialize(()=>new Password());
initialize(()=>new Label(csrftoken));
initialize(()=>new Category(csrftoken));
initialize(()=>new Item());
initialize(()=>new ItemEdit());
initialize(()=>new List());
initialize(()=>new Feature());
initialize(()=>new Barcode());
initialize(()=>new CSRF(csrftoken));
initialize(()=>new Shelf());
initialize(()=>new ArchiveAll());

//<?php } ?>
