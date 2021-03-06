<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use DB;

use App\Services\AuthService;
use App\Services\UserService;
use App\Services\MailService;
use App\Services\AddressService;
use App\Http\Controllers\BaseController;

class UserController extends BaseController
{
    protected $authService;
    protected $userService;
    protected $mailService;
    protected $addressService;


    public function __construct(
        AuthService $authService,
        UserService $userService,
        MailService $mailService,
        AddressService $addressService
    )
    {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->mailService = $mailService;
        $this->addressService = $addressService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $result = $this->userService->getAll();
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * Display a listing of the resource paginate.
     *
     * @return User with paginate
     */
    public function paginate(Request $request)
    {
        $data = $request->only([
            'perPage',
            'page',
            'name',
            'email',
            'role',
            'sort'
        ]);
        try {
            $result = $this->userService->getAllPaginate($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'name',
            'email',
            'role',
            'phone',
            'branch',
            'province',
            'city',
            'district',
            'village',
            'street',
            'postalCode'
        ]);

        $username = explode("@", $data['email'], 2);
        $userData = [
            'password' => env('DEFAULT_PASS_USER', 'user1234'),
            'password_confirmation' => env('DEFAULT_PASS_USER', 'user1234'),
            'role_id' => $data['role'],
            'branch_id' => $data['branch'],
            'username' => $username[0],
        ];
        $userData = array_merge($userData, $data);

        DB::beginTransaction();
        try {
            $user = $this->userService->save($userData);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        // create verify user
        try {
            $verifyUser = $this->authService->createVerifyUser($user->id);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        // send email verification
        try {
            $this->mailService->sendEmailVerification($user, $verifyUser);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        $addressData = [
            'userId' => $user->id,
            'postal_code' => $data['postalCode']
        ];
        $addressData = array_merge($addressData, $data);
        // save address
        try {
            $this->addressService->saveAddressData($addressData);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse('Pengguna berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        try {
            $result = $this->userService->getById($request->userId);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    // /**
    //  * Show the form for editing the specified resource.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function edit(Request $request)
    // {
    //     $data = $request->only([
    //         'name',
    //         'password',
    //         'password_confirmation',
    //         'username',
    //         'phone',
    //     ]);

    //     try {
    //         $result = $this->userService->editUser($data);
    //     } catch (Exception $e) {
    //         return $this->sendError($e->getMessage());
    //     }

    //     return $this->sendResponse(null, $result);
    // }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $data = $request->only([
            'id',
            'name',
            'username',
            'phone',
            'role',
            'branch',
            'province',
            'city',
            'district',
            'village',
            'street',
            'postalCode'
        ]);

        DB::beginTransaction();
        try {
            $result = $this->userService->updateUserService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $data = $request->only([
            'id'
        ]);
        try {
            $result = $this->userService->deleteById($data['id']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function searchName(Request $request)
    {
        try {
            $result = $this->userService->searchByNameService($request->query);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function searchEmail(Request $request)
    {
        try {
            $result = $this->userService->searchByEmailService($request->query);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * Change Password
     *
     * @param Request $request
     */
    public function changePassword(Request $request)
    {
        $data = $request->only([
            'userId',
            'password',
            'password_confirmation',
        ]);
        try {
            $result = $this->userService->changePasswordService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * Forgot Password
     *
     * @param Request $request
     */
    public function forgotPassword(Request $request)
    {
        $data = $request->only([
            'username',
        ]);
        try {
            $result = $this->userService->forgotPasswordService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * update user profile mobile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $data = $request->only([
            'name',
            'username',
            'phone',
            'userId',
            'avatar'
        ]);

        DB::beginTransaction();
        try {
            $result = $this->userService->updateUserProfileService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * Upload avatar
     *
     * @param Request $request
     */
    public function uploadAvatar(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = $this->userService->uploadAvatarService($request);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * Upload avatar
     *
     * @param Request $request
     */
    public function removeAvatar(Request $request)
    {
        $data = $request->only([
            'userId',
        ]);

        DB::beginTransaction();
        try {
            $result = $this->userService->removeAvatarService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * get by name and phone
     */
    public function getByPickupNamePhone(Request $request)
    {
        $data = $request->only([
            'query',
        ]);
        try {
            $result = $this->userService->getByPickupNamePhoneService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get default data name and phone
     */
    public function getDefaultByPickupNamePhone()
    {
        try {
            $result = $this->userService->getDefaultByPickupNamePhoneService();
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * search by name, phone, and email
     */
    public function getByNamePhoneEmail(Request $request)
    {
        $data = $request->only([
            'query',
            'role'
        ]);
        try {
            $result = $this->userService->getByNamePhoneEmailService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get default data name and phone
     */
    public function getDefaultByNamePhone(Request $request)
    {
        $data = $request->only([
            'role'
        ]);
        try {
            $result = $this->userService->getDefaultByNamePhoneService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get list marketing
     */
    public function getMarketingList(Request $request)
    {
        try {
            $result = $this->userService->getMarketingService();
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }
}
