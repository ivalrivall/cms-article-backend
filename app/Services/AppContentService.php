<?php
namespace App\Services;

use App\Repositories\AppContentRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AppContentService {

    protected $appContentRepository;

    public function __construct(AppContentRepository $appContentRepository)
    {
        $this->appContentRepository = $appContentRepository;
    }

    /**
     * create banner
     *
     * @param array $data
     */
    public function createBannerService($data = [])
    {
        $validator = Validator::make($data, [
            // 'order' => 'bail|required',
            'image' => 'bail|required',
        ]);

        DB::beginTransaction();
        if ($validator->fails()) {
            DB::rollback();
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->createBannerRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal manambahkan banner');
        }
        DB::commit();
        return $result;
    }

    /**
     * get data banner paginate
     *
     * @param array $data
     */
    public function getDataBannerService($data)
    {
        $validator = Validator::make($data, [
            'sort' => 'bail|present',
            'perPage' => 'bail|present',
            'page' => 'bail|present',
            // 'order' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->getDataBannerRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan data banner');
        }
        return $result;
    }

    /**
     * get data article paginate
     *
     * @param array $data
     */
    public function getDataArticleService($data)
    {
        $validator = Validator::make($data, [
            'sort' => 'bail|present',
            'perPage' => 'bail|present',
            'page' => 'bail|present',
            'title' => 'bail|present',
            'url' => 'bail|present',
            'description' => 'bail|present',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->getDataArticleRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan data artikel');
        }
        return $result;
    }

    /**
     * delete banner
     *
     * @param array $data
     */
    public function deleteBannerService($data)
    {
        $validator = Validator::make($data, [
            'bannerId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->deleteBannerRepo($data['bannerId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal manghapus banner');
        }

        return $result;
    }

    /**
     * delete article
     *
     * @param array $data
     */
    public function deleteArticleService($data = [])
    {
        $validator = Validator::make($data, [
            'articleId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->deleteArticleRepo($data['articleId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal manghapus artikel');
        }

        return $result;
    }

    /**
     * order banner
     *
     * @param array $data
     */
    // public function editOrderBannerService($data)
    // {
    //     $validator = Validator::make($data, [
    //         'bannerId' => 'bail|required',
    //         'order' => 'bail|required'
    //     ]);

    //     if ($validator->fails()) {
    //         throw new InvalidArgumentException($validator->errors()->first());
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $allBanner = $this->appContentRepository->getBannerRepo();
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         Log::info($e->getMessage());
    //         Log::error($e);
    //         throw new InvalidArgumentException('Gagal mendapat data semua banner');
    //     }

    //     try {
    //         $currentBanner = $this->appContentRepository->getBannerByIdRepo($data['bannerId']);
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         Log::info($e->getMessage());
    //         Log::error($e);
    //         throw new InvalidArgumentException('Gagal mendapat data banner saat ini');
    //     }

    //     // EDIT ORDER BANNER
    //     if (in_array($data['order'], collect($allBanner)->map(function($o) { return $o->order; })->values()->toArray())) {
    //         $currentBannerIdx = array_search($data['bannerId'], array_column($allBanner, 'id'));
    //         $targetIndex = $data['order'] - 1;
    //         $this->moveElement(array_column($allBanner, 'id'), $currentBannerIdx, $targetIndex);
    //         foreach ($allBanner as $key => $value) {
    //             if ($value->id !== $currentBanner->id) {
    //                 if ($value->order = $data['order']) {
    //                     try {
    //                         $order = intval($value->order) + 1;
    //                         $this->appContentRepository->editOrderBanner($value->id, $order);
    //                     } catch (Exception $e) {
    //                         DB::rollback();
    //                         Log::info($e->getMessage());
    //                         Log::error($e);
    //                         throw new InvalidArgumentException('Gagal mengubah urutan banner');
    //                     }
    //                 }
    //                 if ($value->order < $data['order']) {
    //                     try {
    //                         $order = intval($value->order) - 1;
    //                         $this->appContentRepository->editOrderBanner($value->id, $order);
    //                     } catch (Exception $e) {
    //                         DB::rollback();
    //                         Log::info($e->getMessage());
    //                         Log::error($e);
    //                         throw new InvalidArgumentException('Gagal mengubah urutan banner');
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     // EDIT CURRENT BANNER ORDER
    //     try {
    //         $result = $this->appContentRepository->editOrderBanner($currentBanner->id, $data['order']);
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         Log::info($e->getMessage());
    //         Log::error($e);
    //         throw new InvalidArgumentException('Gagal mengubah urutan banner saat ini');
    //     }

    //     DB::commit();
    //     return $result;
    // }

    // private function moveElement($array, $a, $b) {
    //     $out = array_splice($array, $a, 1);
    //     $data = array_splice($array, $b, 0, $out);
    //     return $data;
    // }

    /**
     * upload image service
     *
     * @param array $request
     * @return object
     */
    public function uploadAppContentImageService($request, $type)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|image|max:1024|mimes:jpeg,jpg,png',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $filename = $request->file('image')->getClientOriginalName();

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if (!in_array($extension, ['jpeg','jpg','png','PNG','JPEG','JPG'])) {
            throw new InvalidArgumentException("Ekstensi $extension tidak diperbolehkan");
        }

        DB::beginTransaction();
        if ($type == 'banner') {
            try {
                $result = $this->appContentRepository->uploadBannerRepo($request);
            } catch (Exception $e) {
                DB::rollBack();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mengunggah banner');
            }
        } else {
            try {
                $result = $this->appContentRepository->uploadArticleRepo($request);
            } catch (Exception $e) {
                DB::rollBack();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mengunggah artikel');
            }
        }
        DB::commit();
        return $result;
    }

    /**
     * edit banner service
     */
    public function editBannerService($data = [])
    {
        $validator = Validator::make($data, [
            // 'order' => 'bail|required',
            'image' => 'bail|required',
            'bannerId' => 'bail|required'
        ]);

        DB::beginTransaction();
        if ($validator->fails()) {
            DB::rollback();
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->editBannerRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengubah banner');
        }
        DB::commit();
        return $result;
    }

    /**
     * edit article service
     */
    public function editArticleService($data = [])
    {
        $validator = Validator::make($data, [
            'title' => 'bail|required',
            'description' => 'bail|present',
            'url' => 'bail|required',
            'image' => 'bail|required',
            'articleId' => 'bail|required'
        ]);

        DB::beginTransaction();
        if ($validator->fails()) {
            DB::rollback();
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->editArticleRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengubah artikel');
        }
        DB::commit();
        return $result;
    }

    /**
     * get banner by id
     */
    public function getBannerByIdService($bannerId)
    {
        try {
            $banner = $this->appContentRepository->getBannerByIdRepo($bannerId);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapat data banner');
        }
        return $banner;
    }

    /**
     * get article by id
     */
    public function getArticleByIdService($articleId)
    {
        try {
            $article = $this->appContentRepository->getArticleByIdRepo($articleId);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapat data article');
        }
        return $article;
    }

    /**
     * delete app content file
     *
     * @param array $data
     */
    public function deleteAppContentFileService($data)
    {
        $validator = Validator::make($data, [
            'path' => 'bail|required',
            'type' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $this->appContentRepository->deleteAppContentFileRepo($data['path'], $data['type']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal manghapus file');
        }

        return true;
    }

    /**
     * create banner
     *
     * @param array $data
     */
    public function createArticleService($data = [])
    {
        $validator = Validator::make($data, [
            'title' => 'bail|required',
            'url' => 'bail|required',
            'image' => 'bail|required',
        ]);

        DB::beginTransaction();
        if ($validator->fails()) {
            DB::rollback();
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->createArticleRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal manambahkan artikel');
        }
        DB::commit();
        return $result;
    }

    /**
     * get all data banner
     */
    public function getAllDataBannerService()
    {
        try {
            $result = $this->appContentRepository->getBannerRepo();
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan semua data banner');
        }
        return $result;
    }

    /**
     * get data notification service
     */
    public function getDataNotificationService($data = [])
    {
        $validator = Validator::make($data, [
            'sort' => 'bail|present',
            'perPage' => 'bail|present',
            'page' => 'bail|present',
            'title' => 'bail|present',
            'body' => 'bail|present',
            'type' => 'bail|present',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->getPaginationNotificationRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan data template notification');
        }
        return $result;
    }

    /**
     * update template notification
     */
    public function updateTemplateNotification($data = [])
    {
        $validator = Validator::make($data, [
            'id' => 'bail|present',
            'title' => 'bail|present',
            'body' => 'bail|present',
            'type' => 'bail|present'
            // 'image' => 'bail|present|nullable'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->updateTemplateNotificationRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengubah data template notification');
        }
        return $result;
    }

    /**
     * get history notification
     */
    public function getNotificationHistoryService($data = [])
    {
        $validator = Validator::make($data, [
            'userId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->getNotificationHistoryRepo($data['userId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapat data riwayat notifikasi');
        }
        return $result;
    }

    /**
     * delete history notification
     */
    public function deleteNotificationHistoryService($data = [])
    {
        $validator = Validator::make($data, [
            'id' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->deleteNotificationHistoryRepo($data['id']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal menghapus data riwayat notifikasi');
        }
        return $result;
    }

    /**
     * read history notification
     */
    public function readNotificationHistoryService($data = [])
    {
        $validator = Validator::make($data, [
            'id' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->appContentRepository->readNotificationHistoryRepo($data['id']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengubah status sudah dibaca');
        }
        return $result;
    }
}
