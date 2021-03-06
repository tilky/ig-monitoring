<?php

namespace app\models;

use app\components\UidAttributeBehavior;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "account".
 *
 * @property int $id
 * @property string $uid
 * @property string $name
 * @property string $username
 * @property string $profile_pic_url
 * @property string $full_name
 * @property string $biography
 * @property string $external_url
 * @property string $instagram_id
 * @property string $updated_at
 * @property string $created_at
 * @property bool $monitoring
 * @property int $proxy_id
 * @property int $proxy_tag_id
 * @property bool $disabled
 * @property int $accounts_monitoring_level
 * @property string $accounts_default_tags
 * @property bool $is_valid [tinyint(1)]
 * @property int $invalidation_type_id [int(11)]
 * @property int $invalidation_count [int(11)]
 * @property string $update_stats_after [datetime]
 *
 * @property string $usernamePrefixed
 * @property string $displayName
 *
 * @property AccountStats $lastAccountStats
 *
 * @property AccountInvalidationType $invalidationType
 * @property Proxy $proxy
 * @property Tag $proxyTag
 * @property AccountNote[] $accountNotes
 * @property AccountStats[] $accountStats
 * @property AccountTag[] $accountTags
 * @property Tag[] $tags
 * @property Media[] $media
 * @property MediaAccount[] $mediaAccounts
 * @property \app\models\Account[] $accounts
 */
class Account extends \yii\db\ActiveRecord
{
    public $occurs;

    public static function usedTags()
    {
        return Tag::find()
            ->distinct()
            ->innerJoin('account_tag', 'tag.id=account_tag.tag_id')
            ->orderBy('tag.slug ASC')
            ->all();
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'time' => TimestampBehavior::class,
            'uid' => UidAttributeBehavior::class,
        ]);
    }

    public function getDisplayName()
    {
        return $this->name ?: $this->getUsernamePrefixed();
    }

    public function getUsernamePrefixed()
    {
        return "@{$this->username}";
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'account';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username'], 'required'],
            [['updated_at', 'created_at', 'accounts_default_tags'], 'safe'],
            [['proxy_id', 'proxy_tag_id', 'occurs'], 'integer'],
            ['accounts_monitoring_level', 'integer', 'min' => 0],
            [['name', 'username', 'profile_pic_url', 'full_name', 'biography', 'external_url', 'instagram_id', '!uid'], 'string', 'max' => 255],
            [['monitoring', 'disabled', 'is_valid'], 'boolean'],
            [['username'], 'unique'],
            [['proxy_id'], 'exist', 'skipOnError' => true, 'targetClass' => Proxy::class, 'targetAttribute' => ['proxy_id' => 'id']],
            [['proxy_tag_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tag::class, 'targetAttribute' => ['proxy_tag_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'username' => 'Username',
            'profile_pic_url' => 'Profile Pic Url',
            'full_name' => 'Full Name',
            'biography' => 'Biography',
            'external_url' => 'External Url',
            'instagram_id' => 'Instagram ID',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
            'monitoring' => 'Monitoring',
            'proxy_id' => 'Proxy ID',
            'proxy_tag_id' => 'Proxy Tag ID',
            'accounts_monitoring_level' => 'Accounts Monitoring Level',
        ];
    }

    public function attributeHints()
    {
        return [
            'name' => 'The name displayed in the lists, if empty, the \'username\' will be used.',
            'accounts_monitoring_level' => 'Automatically monitors discovered accounts. Be careful.',
            'accounts_default_tags' => 'Automatically tag discovered accounts. If not set, parent tags will be used.',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvalidationType()
    {
        return $this->hasOne(AccountInvalidationType::class, ['id' => 'invalidation_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProxy()
    {
        return $this->hasOne(Proxy::class, ['id' => 'proxy_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProxyTag()
    {
        return $this->hasOne(Tag::class, ['id' => 'proxy_tag_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccountNotes()
    {
        return $this->hasMany(AccountNote::class, ['account_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccountStats()
    {
        return $this->hasMany(AccountStats::class, ['account_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastAccountStats()
    {
        return $this->hasOne(AccountStats::class, ['account_id' => 'id'])
            ->orderBy('account_stats.id DESC')
            ->limit(1);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccountTags()
    {
        return $this->hasMany(AccountTag::class, ['account_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])->via('accountTags');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedia()
    {
        return $this->hasMany(Media::class, ['account_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMediaMediaAccounts()
    {
        return $this->hasMany(MediaAccount::class, ['media_id' => 'id'])
            ->via('media');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccounts()
    {
        return $this->hasMany(Account::class, ['id' => 'account_id'])
            ->via('mediaMediaAccounts');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMediaAccounts()
    {
        return $this->hasMany(MediaAccount::class, ['account_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return AccountQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AccountQuery(get_called_class());
    }
}
