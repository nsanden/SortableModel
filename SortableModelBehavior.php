<?php
/**
 * Behavior for CActiveRecord model to store order of records.
 * When you create new model and save it, it appears at the end of list.
 * You can manually change the position of record with {@see moveUp} and {@see moveDown} methods.
 * When you delete model, all subsequent models are moved up one position.
 *
 * You must manually add field for position in table.
 * 	ALTER TABLE `v_forums`
 * 	ADD `order` tinyint unsigned NOT NULL,
 * 	ADD UNIQUE `order` (`order`);
 *
 * @author wapmorgan (wapmorgan@gmail.com)
 */
class SortableModelBehavior extends CActiveRecordBehavior {
	/**
	 * Field that stores 1-based order
	 */
	public $orderField = 'order';

	/**
	 * BeforeSave event handler. Sets orderField.
	 */
	public function beforeSave($event) {
		if ($this->owner->isNewRecord) {
			$number = ($data = $this->createCommand()->select(new CDbExpression('MAX(`order`)'))->queryScalar()) > 0
				? $data + 1
				: 1;
			$this->owner->setAttribute($this->orderField, $number);
		}
	}

	/**
	 * Sets default order
	 */
	public function beforeFind($event) {
		$this->owner->dbCriteria->order = '`'.$this->orderField.'`';
	}

	/**
	 * AfterDelete event handler. Updates order.
	 */
	public function afterDelete($event) {
		$this->createCommand()->update(
			$this->owner->tableName(),
			array($this->orderField => new CDbExpression('`'.$this->orderField.'` - 1')),
			'`'.$this->orderField.'` > :position',
			array(':position' => $this->owner->attributes[$this->orderField])
		);
	}

	/**
	 * Moves record closer to the top (decreases order field).
	 */
	public function moveUp() {
		if ($this->owner->attributes[$this->orderField] > 1) {
			if ($this->owner->findByAttributes(array($this->orderField => 0)) !== null)
				throw new Exception('Table order is locked!');
			if (($externalTransaction = $this->owner->dbConnection->currentTransaction) === null)
				$transaction = $this->owner->dbConnection->beginTransaction();
			$position = $this->owner->attributes[$this->orderField];
			$this->owner->setAttribute($this->orderField, 0);
			$this->owner->save(false, array($this->orderField));
			$pair = $this->owner->findByAttributes(array($this->orderField => $position - 1));
			$pair->setAttribute($this->orderField, $position);
			$pair->save(false, array($this->orderField));
			$this->owner->setAttribute($this->orderField, $position - 1);
			$this->owner->save(false, array($this->orderField));
			if ($externalTransaction === null)
				$transaction->commit();
			return true;
		}
	}

	/**
	 * Moves record closer to the bottom (increases order field).
	 */
	public function moveDown() {
		$count = $this->owner->count();
		if ($this->owner->attributes[$this->orderField] < $count) {
			if ($this->owner->findByAttributes(array($this->orderField => 0)) !== null)
				throw new Exception('Table order is locked!');
			if (($externalTransaction = $this->owner->dbConnection->currentTransaction) === null)
				$transaction = $this->owner->dbConnection->beginTransaction();
			$position = $this->owner->attributes[$this->orderField];
			$this->owner->setAttribute($this->orderField, 0);
			$this->owner->save();
			$pair = $this->owner->findByAttributes(array($this->orderField => $position + 1));
			$pair->setAttribute($this->orderField, $position);
			$pair->save();
			$this->owner->setAttribute($this->orderField, $position + 1);
			$this->owner->save();
			if ($externalTransaction === null)
				$transaction->commit();
			return true;
		}
	}

	/**
	 * Creates DB command
	 */
	protected function createCommand() {
		return $this->owner->dbConnection->createCommand()->from($this->owner->tableName());
	}
}
