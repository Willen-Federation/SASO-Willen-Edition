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

let csrftoken = '<?php echo $v->csrftoken; ?>';

new Password();
new Label(csrftoken);
new Category(csrftoken);
new Item();
new ItemEdit();
new List();
new Feature();
new Barcode();
new CSRF(csrftoken);
new Shelf();
new ArchiveAll();

//<?php } ?>
