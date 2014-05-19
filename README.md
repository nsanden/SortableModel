**SortableModel** is a helper for managing manually created order of models **to Yii web framework**.

---
## Install
1. Copy/Clone dir in protected/extensions/SortableModel:
```
git clone https://github.com/wapmorgan/SortableModel.git protected/extensions/SortableModel
```
2. Open your model and add behavior in `behaviors()` method like this:
```php
public function behaviors() {
		return array(
			'SortableModel' => array(
				'class' => 'ext.SortableModel.SortableModelBehavior'
				/* optional parameters */
				//'orderField' => 'order',
				//'condition' => 'user_id = :user_id',
				//'params' => array(':user_id' => 1)
			),
		);
	}
```
3. All newly created models will be at the end of list.

4. To move them, call `moveUp()` or `moveDown()`.

5. If you delete a record, all the subsequent records are moved up one position.
