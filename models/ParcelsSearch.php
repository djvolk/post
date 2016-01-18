<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Parcels;

/**
 * ParcelsSearch represents the model behind the search form about `app\models\Parcels`.
 */
class ParcelsSearch extends Parcels {

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'order_id', 'statusID', 'delivery_period', 'delivery_lateness'], 'integer'],
            [['spi', 'shipping_type', 'order_time', 'shop_status', 'order_amount', 'status_time', 'mailed', 'delivery_status', 'status'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params) {
        $query = Parcels::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['order_id'=>SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate())
        {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'order_id' => $this->order_id,
            'statusID' => $this->statusID,
            'delivery_period' => $this->delivery_period,
            'delivery_lateness' => $this->delivery_lateness,
        ]);

        if ($this->status != null && $this->status != '')
        {
            if ($this->status == 1)
                $status = 'enabled';
            else
                $status = 'disabled';
        } else
            $status = null;

        if ($this->mailed != null && $this->mailed != '')
        {
            if ($this->mailed == 1)
                $mailed = 'yes';
            else
                $mailed = 'no';
        } else
            $mailed = null;


        $query->andFilterWhere(['like', 'spi', $this->spi])
                ->andFilterWhere(['like', 'shipping_type', $this->shipping_type])
                ->andFilterWhere(['like', 'order_time', $this->order_time])
                ->andFilterWhere(['like', 'shop_status', $this->shop_status])
                ->andFilterWhere(['like', 'order_amount', $this->order_amount])
                ->andFilterWhere(['like', 'status_time', $this->status_time])
                ->andFilterWhere(['like', 'mailed', $mailed])
                ->andFilterWhere(['like', 'delivery_status', $this->delivery_status])
                ->andFilterWhere(['like', 'status', $status]);

        return $dataProvider;
    }

}
