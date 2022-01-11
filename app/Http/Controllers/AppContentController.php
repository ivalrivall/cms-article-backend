<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AppContentService;
use App\Http\Controllers\BaseController;
use Exception;
use DB;


class AppContentController extends BaseController
{
    protected $appContentService;

    public function __construct(AppContentService $appContentService)
    {
        $this->appContentService = $appContentService;
    }

    /**
     * create banner.
     */
    public function createBanner(Request $request)
    {
        $data = $request->only([
            'userId',
            'image',
            // 'order'
        ]);

        DB::beginTransaction();
        // UPLOAD IMAGE
        try {
            $image = $this->appContentService->uploadAppContentImageService($request, 'banner');
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        try {
            $payload = [
                'image' => $image['path'],
                // 'order' => $data['order']
            ];
            $result = $this->appContentService->createBannerService($payload);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * get data banner.
     */
    public function getDataBannerPaginate(Request $request)
    {
        $data = $request->only([
            'perPage',
            'sort',
            'page',
            // 'order'
        ]);

        try {
            $result = $this->appContentService->getDataBannerService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * get all data banner.
     */
    public function getAllDataBanner(Request $request)
    {
        try {
            $result = $this->appContentService->getAllDataBannerService();
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * get data article.
     */
    public function getDataArticlePaginate(Request $request)
    {
        $data = $request->only([
            'perPage',
            'sort',
            'page',
            'title',
            'description',
            'url'
        ]);

        try {
            $result = $this->appContentService->getDataArticleService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * delete banner.
     */
    public function deleteBanner(Request $request)
    {
        $data = $request->only([
            'bannerId',
        ]);

        try {
            $result = $this->appContentService->deleteBannerService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * delete article.
     */
    public function deleteArticle(Request $request)
    {
        $data = $request->only([
            'articleId',
        ]);

        try {
            $result = $this->appContentService->deleteArticleService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * upload image
     */
    public function uploadImage(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = $this->appContentService->uploadAppContentImageService($request, $request->type);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * edit banner.
     */
    public function editBanner(Request $request)
    {
        $data = $request->only([
            'bannerId',
            'image',
            // 'order'
        ]);

        DB::beginTransaction();
        try {
            $currentBanner = $this->appContentService->getBannerByIdService($data['bannerId']);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        if ($request->hasFile('image')) {
            // UPLOAD IMAGE
            try {
                $image = $this->appContentService->uploadAppContentImageService($request, 'banner');
            } catch (Exception $e) {
                DB::rollback();
                return $this->sendError($e->getMessage());
            }

            try {
                $payload = [
                    'path' => $currentBanner->image,
                    'type' => 'banner'
                ];
                $this->appContentService->deleteAppContentFileService($payload);
            } catch (Exception $e) {
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
        } else {
            $image = [
                'path' => $currentBanner->image
            ];
        }

        // EDIT ORDER BANNER
        // try {
        //     $this->appContentService->editOrderBannerService($data);
        // } catch (Exception $e) {
        //     DB::rollback();
        //     return $this->sendError($e->getMessage());
        // }

        // EDIT DATA BANNER
        try {
            $payload = [
                'bannerId' => $data['bannerId'],
                'image' => $image['path'],
                // 'order' => $data['order']
            ];
            $result = $this->appContentService->editBannerService($payload);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        DB::commit();
        return $this->sendResponse(null, $result);
    }


    /**
     * create article.
     */
    public function createArticle(Request $request)
    {
        $data = $request->only([
            'userId',
            'image',
            'url',
            'title',
            'description'
        ]);

        DB::beginTransaction();
        // UPLOAD IMAGE
        try {
            $image = $this->appContentService->uploadAppContentImageService($request, 'article');
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        try {
            $payload = [
                'image' => $image['path'],
                'url' => $data['url'],
                'title' => $data['title'],
                'description' => $data['description'],
            ];
            $result = $this->appContentService->createArticleService($payload);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * edit article.
     */
    public function editArticle(Request $request)
    {
        $data = $request->only([
            'articleId',
            'image',
            'title',
            'url',
            'description',
        ]);

        DB::beginTransaction();
        try {
            $currentArticle = $this->appContentService->getArticleByIdService($data['articleId']);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        if ($request->hasFile('image')) {
            // UPLOAD IMAGE
            try {
                $image = $this->appContentService->uploadAppContentImageService($request, 'article');
            } catch (Exception $e) {
                DB::rollback();
                return $this->sendError($e->getMessage());
            }

            try {
                $payload = [
                    'path' => $currentArticle->image,
                    'type' => 'article'
                ];
                $this->appContentService->deleteAppContentFileService($payload);
            } catch (Exception $e) {
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
        } else {
            $image = [
                'path' => $currentArticle->image
            ];
        }

        // EDIT DATA ARTICLE
        try {
            $payload = [
                'articleId' => $data['articleId'],
                'image' => $image['path'],
                'url' => $data['url'],
                'title' => $data['title'],
                'description' => $data['description']
            ];
            $result = $this->appContentService->editArticleService($payload);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * get notification paginate.
     */
    public function getDataNotificationPaginate(Request $request)
    {
        $data = $request->only([
            'perPage',
            'sort',
            'page',
            'title',
            'body',
            'type'
        ]);

        try {
            $result = $this->appContentService->getDataNotificationService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * update template notification
     */
    public function editTemplateNotification(Request $request)
    {
        $data = $request->only([
            'id',
            'title',
            'body',
            'type'
            // 'image'
        ]);

        try {
            $result = $this->appContentService->updateTemplateNotification($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * get notification history
     */
    public function getNotificationHistory(Request $request)
    {
        $data = $request->only([
            'userId'
        ]);

        try {
            $result = $this->appContentService->getNotificationHistoryService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * delete notification history
     */
    public function deleteNotificationHistory(Request $request)
    {
        $data = $request->only([
            'id'
        ]);

        try {
            $result = $this->appContentService->deleteNotificationHistoryService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * read notification history
     */
    public function readNotificationHistory(Request $request)
    {
        $data = $request->only([
            'id'
        ]);

        try {
            $result = $this->appContentService->readNotificationHistoryService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }
}
