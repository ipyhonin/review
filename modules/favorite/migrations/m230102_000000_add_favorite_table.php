<?
use admin\models\User;

class m230102_000000_add_favorite_table extends \yii\db\Migration {

    public $tableName = 'admin_module_favorite';

    public function safeUp() 
    {
        $this->createTable($this->tableName, [
            'id'            => $this->primaryKey(),
            'user_id'       => $this->integer()->notNull(),
            'item_class'    => $this->string()->notNull(),
            'item_id'       => $this->integer()->notNull(),
            'comment'       => $this->text(),
        ]);
        $this->db->createCommand()
            ->addUnique('unique-' . $this->tableName . '-user_id-item_class-item_id', $this->tableName, 'user_id, item_class, item_id')
            ->execute();
        $this->createIndex('idx-' . $this->tableName . '-user_id', $this->tableName, 'user_id');
        $this->createIndex('idx-' . $this->tableName . '-user_id-item_class', $this->tableName, 'user_id, item_class');
        $this->addForeignKey(
            'fk-' . $this->tableName . '-user_id',
            $this->tableName,
            'user_id',
            User::tableName(),
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-' . $this->tableName . '-user_id', $this->tableName);
        $this->dropIndex('idx-' . $this->tableName . '-user_id', $this->tableName);
        $this->dropIndex('idx-' . $this->tableName . '-user_id-item_class', $this->tableName);
        $this->dropTable($this->tableName);
    }
}
