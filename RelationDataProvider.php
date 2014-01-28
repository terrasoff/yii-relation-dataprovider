<?php

class RelationDataProvider extends CActiveDataProvider
{

    /** @var bool вместе с отношениями типа MANY_MANY */
    public $together = true;

    /** TODO @var array отрабатывать отношения типа MANY_MANY из списка */
    public $with = array();

    /** @var CCacheDependency кешировать отношения */
    public $dependency = null;

    /**
     * @var int
     * кешировать отношения типа MANY_MANY
     * 0 = кэш неактивен
     * int = время кеширования в секундах
     */
    public $cachetime = 0;

    /**
     * Fetches the data from the persistent data storage.
     * @return array list of data items
     */
    protected function fetchData()
    {
        $criteria=clone $this->getCriteria();
        if(($pagination=$this->getPagination())!==false)
        {
            $pagination->setItemCount($this->getTotalItemCount());
            $pagination->applyLimit($criteria);
        }
        if(($sort=$this->getSort())!==false)
            $sort->applyOrder($criteria);

        if ($this->together)
        {
            $class = $this->modelClass;
            $models = $class::model()
                ->cache($this->cachetime, $this->dependency)
                ->findAll($criteria);

            $list = array();
            foreach ($models as $model)
                $list[] = $model->primaryKey;

            foreach ($this->model->relations() as $name=>$relation) {
                // TODO пока берем все MANY_MANY
                if ($relation[0] == 'CHasManyRelation') {
                    $class = new $relation[1];
                    $criteria = new CDbCriteria();
                    $criteria->addInCondition($relation[2], $list);
                    $items = $class::model()->cache($this->cachetime, $this->dependency)->findAll($criteria);

                    // распихиваем отношения по моделям
                    foreach ($models as $model) {
                        $relatedModels = array();
                        // TODO: оптимизировать - найденное исключать
                        foreach ($items as $item)
                            if ($item->$relation[2] == $model->primaryKey)
                                $relatedModels[] = $item;
                        $model->$name = $relatedModels;
                    }
                }
            }
            return $models;
        } else {
            return CActiveRecord::model($this->modelClass)->findAll($criteria);
        }
    }

}
