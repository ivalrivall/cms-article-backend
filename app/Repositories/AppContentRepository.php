<?php

namespace App\Repositories;

use App\Models\Banner;
use App\Models\Article;
use App\Models\TemplateNotification;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

use InvalidArgumentException;

class AppContentRepository
{
    protected $banner;
    protected $article;
    protected $templateNotif;
    protected $notification;
    protected $user;

    public function __construct(Banner $banner, Article $article, TemplateNotification $templateNotif, Notification $notification, User $user)
    {
        $this->banner = $banner;
        $this->article = $article;
        $this->templateNotif = $templateNotif;
        $this->notification = $notification;
        $this->user = $user;
    }

    /**
     * create banner
     *
     * @param array $data
     * @return Banner
     */
    public function createBannerRepo($data = [])
    {
        $banner = new $this->banner;
        // $banner->order = $data['order'];
        $banner->image = $data['image'];
        $banner->save();
        Cache::put('banner:'.$banner['id'], $banner, env('CACHE_EXP'));
        Cache::forget('banners');
        return $banner;
    }

    /**
     * create article
     *
     * @param array $data
     * @return Article
     */
    public function createArticleRepo($data = [])
    {
        $article = new $this->article;
        $article->url = $data['url'];
        $article->image = $data['image'];
        $article->description = $data['description'];
        $article->title = $data['title'];
        $article->save();
        Cache::put('article:'.$article['id'], $article, env('CACHE_EXP'));
        Cache::forget('articles');
        return $article;
    }

    /**
     * get all banner
     *
     * @param array $data
     * @return Banner
     */
    public function getBannerRepo()
    {
        if (Cache::has('banners')) {
            $banners = Cache::get('banners');
        } else {
            $banners = $this->banner->all();
            Cache::put('banners', $banners, env('CACHE_EXP'));
        }
        return $banners;
    }

    /**
     * edit order banner
     */
    // public function editOrderBanner($bannerId, $order)
    // {
    //     $banner = $this->banner->find($bannerId);
    //     $banner->order = $order;
    //     $banner->save();
    //     return $banner;
    // }

    /**
     * edit banner
     */
    public function editBannerRepo($data = [])
    {
        $banner = $this->banner->find($data['bannerId']);
        if (!$banner) {
            throw new InvalidArgumentException('Banner tidak ditemukan');
        }
        // $banner->order = $data['order'];
        $banner->image = $data['image'];
        $banner->save();
        Cache::put('banner:'.$banner['id'], $banner, env('CACHE_EXP'));
        Cache::forget('banners');
        return $banner;
    }

    /**
     * edit artikel
     */
    public function editArticleRepo($data = [])
    {
        $article = $this->article->find($data['articleId']);
        if (!$article) {
            throw new InvalidArgumentException('Artikel tidak ditemukan');
        }
        $article->title = $data['title'];
        $article->description = $data['description'];
        $article->url = $data['url'];
        $article->image = $data['image'];
        $article->save();
        Cache::put('article:'.$article['id'], $article, env('CACHE_EXP'));
        Cache::forget('articles');
        return $article;
    }

    /**
     * get banner by id
     *
     * @param array $data
     * @return Banner
     */
    public function getBannerByIdRepo($id)
    {
        if (Cache::has('banner:'.$id)) {
            $banner = Cache::get('banner:'.$id);
        } else {
            $banner = $this->banner->find($id);
            if (!$banner) {
                throw new InvalidArgumentException('Banner tidak ditemukan');
            }
            Cache::put('banner:'.$id, $banner, env('CACHE_EXP'));
            Cache::forget('banners');
        }
        return $banner;
    }

    /**
     * get article by id
     *
     * @param array $data
     * @return Article
     */
    public function getArticleByIdRepo($id)
    {
        if (Cache::has('article:'.$id)) {
            $article = Cache::get('article:'.$id);
        } else {
            $article = $this->article->find($id);
            if (!$article) {
                throw new InvalidArgumentException('Artikel tidak ditemukan');
            }
            Cache::put('article:'.$id, $article, env('CACHE_EXP'));
            Cache::forget('articles');
        }
        return $article;
    }

    /**
     * delete banner
     *
     * @param array $data
     * @return Banner
     */
    public function deleteBannerRepo($bannerId)
    {
        $data = $this->banner->find($bannerId);
        if (!$data) {
            throw new InvalidArgumentException('Banner tidak ditemukan');
        }
        $image = $data->image;
        $image = explode('/', $image);
        $image = end($image);
        $file = Storage::disk('storage_banner')->delete($image);
        if ($file) {
            $data->delete();
        } else {
            throw new InvalidArgumentException('Banner gagal dihapus');
        }
        Cache::forget('banner:'.$bannerId);
        Cache::forget('banners');
        return $data;
    }

    /**
     * delete article
     *
     * @param array $data
     * @return Article
     */
    public function deleteArticleRepo($articleId)
    {
        $data = $this->article->find($articleId);
        if (!$data) {
            throw new InvalidArgumentException('Artikel tidak ditemukan');
        }
        $image = $data->image;
        $image = explode('/', $image);
        $image = end($image);
        $file = Storage::disk('storage_article')->delete($image);
        if ($file) {
            $data->delete();
        } else {
            throw new InvalidArgumentException('Artikel gagal dihapus');
        }
        Cache::forget('article:'.$articleId);
        Cache::forget('articles');
        return $data;
    }

    /**
     * get data banner
     *
     * @param array $data
     * @return Banner
     */
    public function getDataBannerRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $sort = $data['sort'];

        // $orderData = $data['order'];

        $banner = $this->banner;

        if (empty($perPage)) {
            $perPage = 10;
        }

        if (!empty($sort['field'])) {
            $order = $sort['order'];
            if ($order == 'ascend') {
                $order = 'asc';
            } else if ($order == 'descend') {
                $order = 'desc';
            } else {
                $order = 'desc';
            }
            switch ($sort['field']) {
                case 'id':
                    $banner = $banner->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'order':
                    $banner = $banner->sortable([
                        'order' => $order
                    ]);
                    break;
                case 'created_at':
                    $banner = $banner->sortable([
                        'created_at' => $order
                    ]);
                    break;
                case 'updated_at':
                    $banner = $banner->sortable([
                        'updated_at' => $order
                    ]);
                    break;
                default:
                    $banner = $banner->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        // if (!empty($orderData)) {
        //     $banner = $banner->where('order', 'ilike', '%'.$orderData.'%');
        // }

        $result = $banner->paginate($perPage);

        return $result;
    }

    /**
     * Upload image content
     *
     * @param Request $request
     * @return array
     */
    public function uploadBannerRepo($request)
    {
        $file = $request->file('image');
        $extension  = $file->getClientOriginalExtension();
        $timestamp = Carbon::now('Asia/Jakarta')->timestamp;
        $banner = Image::make($file->path());
        $banner->resize(null, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $name = 'banner'.$timestamp.'.'.$extension;
        $folder = storage_path('app/public/upload/banner/');
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $banner = $banner->save(storage_path('app/public/upload/banner/').$name);
        // Storage::disk('storage_profile')->put($name, File::get($avatar));
        $bannerUrl              = '/upload/banner/'.$name;
        return [
            'base_url' => env('APP_URL').'/public/storage',
            'path' => $bannerUrl
        ];
    }

    /**
     * Upload image content
     *
     * @param Request $request
     * @return array
     */
    public function uploadArticleRepo($request)
    {
        $file = $request->file('image');
        $extension  = $file->getClientOriginalExtension();
        $timestamp = Carbon::now('Asia/Jakarta')->timestamp;
        $article = Image::make($file->path());
        $article->resize(null, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $name = 'article'.$timestamp.'.'.$extension;
        $folder = storage_path('app/public/upload/article/');
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $article = $article->save(storage_path('app/public/upload/article/').$name);
        // Storage::disk('storage_profile')->put($name, File::get($avatar));
        $articleUrl              = '/upload/article/'.$name;
        return [
            'base_url' => env('APP_URL').'/public/storage',
            'path' => $articleUrl
        ];
    }

    /**
     * delete file
     * @param string $path
     * @param string $type
     */
    public function deleteAppContentFileRepo($path, $type)
    {
        $image = explode('/', $path);
        $image = end($image);
        if ($type == 'banner') {
            Storage::disk('storage_banner')->delete($image);
        }
        if ($type == 'article') {
            Storage::disk('storage_article')->delete($image);
        }
    }

    /**
     * get data article
     *
     * @param array $data
     * @return Article
     */
    public function getDataArticleRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $sort = $data['sort'];

        $title = $data['title'];
        $description = $data['description'];
        $url = $data['url'];

        $article = $this->article;

        if (empty($perPage)) {
            $perPage = 10;
        }

        if (!empty($sort['field'])) {
            $order = $sort['order'];
            if ($order == 'ascend') {
                $order = 'asc';
            } else if ($order == 'descend') {
                $order = 'desc';
            } else {
                $order = 'desc';
            }
            switch ($sort['field']) {
                case 'id':
                    $article = $article->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'url':
                    $article = $article->sortable([
                        'url' => $order
                    ]);
                    break;
                case 'description':
                    $article = $article->sortable([
                        'description' => $order
                    ]);
                    break;
                case 'title':
                    $article = $article->sortable([
                        'title' => $order
                    ]);
                    break;
                case 'created_at':
                    $article = $article->sortable([
                        'created_at' => $order
                    ]);
                    break;
                case 'updated_at':
                    $article = $article->sortable([
                        'updated_at' => $order
                    ]);
                    break;
                default:
                    $article = $article->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($title)) {
            $article = $article->where('title', 'ilike', '%'.$title.'%');
        }

        if (!empty($description)) {
            $article = $article->where('description', 'ilike', '%'.$description.'%');
        }

        if (!empty($url)) {
            $article = $article->where('url', 'ilike', '%'.$url.'%');
        }

        $result = $article->paginate($perPage);

        return $result;
    }

    /**
     * get pagination data notification
     *
     * @param array $data
     * @return TemplateNotification
     */
    public function getPaginationNotificationRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $sort = $data['sort'];

        $title = $data['title'];
        $body = $data['body'];
        $type = $data['type'];

        $templateNotif = $this->templateNotif;

        if (empty($perPage)) {
            $perPage = 10;
        }

        if (!empty($sort['field'])) {
            $order = $sort['order'];
            if ($order == 'ascend') {
                $order = 'asc';
            } else if ($order == 'descend') {
                $order = 'desc';
            } else {
                $order = 'desc';
            }
            switch ($sort['field']) {
                case 'id':
                    $templateNotif = $templateNotif->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'title':
                    $templateNotif = $templateNotif->sortable([
                        'title' => $order
                    ]);
                    break;
                case 'body':
                    $templateNotif = $templateNotif->sortable([
                        'body' => $order
                    ]);
                    break;
                case 'type':
                    $templateNotif = $templateNotif->sortable([
                        'type' => $order
                    ]);
                    break;
                case 'created_at':
                    $templateNotif = $templateNotif->sortable([
                        'created_at' => $order
                    ]);
                    break;
                case 'updated_at':
                    $templateNotif = $templateNotif->sortable([
                        'updated_at' => $order
                    ]);
                    break;
                default:
                    $templateNotif = $templateNotif->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($title)) {
            $templateNotif = $templateNotif->where('title', 'ilike', '%'.$title.'%');
        }

        if (!empty($body)) {
            $templateNotif = $templateNotif->where('body', 'ilike', '%'.$body.'%');
        }

        if (!empty($type)) {
            $templateNotif = $templateNotif->where('type', 'ilike', '%'.$type.'%');
        }

        $result = $templateNotif->paginate($perPage);

        return $result;
    }

    /**
     * update data notification template
     */
    public function updateTemplateNotificationRepo($data = [])
    {
        $template = $this->templateNotif->find($data['id']);
        if (!$template) {
            throw new InvalidArgumentException('Template notifikasi tidak ditemukan');
        }
        $template->title = $data['title'];
        $template->body = $data['body'];
        // $template->image = $data['image'];
        $template->save();
        return $template;
    }

    /**
     * get data notification
     */
    public function getDataNotificationRepo($type)
    {
        $template = $this->templateNotif->where('type', $type)->first();
        if (!$template) {
            throw new InvalidArgumentException('Template notifikasi tidak ditemukan');
        }
        return $template;
    }

    /**
     * get data notification history for user
     */
    public function getNotificationHistoryRepo($userId)
    {
        $user = $this->user->find($userId);
        return $user->notifications;
    }

    /**
     * delete data notification history for user
     */
    public function deleteNotificationHistoryRepo($id)
    {
        $notification = $this->notification->find($id);
        $notification->delete();
        return $notification;
    }

    /**
     * read data notification history for user
     */
    public function readNotificationHistoryRepo($id)
    {
        $notification = $this->notification->find($id);
        $notification->read_at = Carbon::now('Asia/Jakarta')->toDateTimeString();
        $notification->save();
        return $notification;
    }
}
