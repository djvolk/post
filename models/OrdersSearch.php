<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Orders;

/**
 * OrdersSearch represents the model behind the search form about `app\models\Orders`.
 */
class OrdersSearch extends Orders
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'order_id', 'statusID'], 'integer'],
            [['spi', 'fio', 'phone', 'amount', 'shop_status', 'delivery_status', 'delivery_time', 'sms_status', 'status'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
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
    public function search($params)
    {
        $query = Orders::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'order_id' => $this->order_id,
            'statusID' => $this->statusID,
            'delivery_time' => $this->delivery_time,
        ]);
        
        $query->andFilterWhere(['like', 'spi', $this->spi])
            ->andFilterWhere(['like', 'fio', $this->fio])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'amount', $this->amount])
            ->andFilterWhere(['like', 'shop_status', $this->shop_status])
            ->andFilterWhere(['like', 'delivery_status', $this->delivery_status])
            ->andFilterWhere(['like', 'sms_status', $this->sms_status])    
            ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }
}
