<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Verify</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <!-- Styles -->
        <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

        <style>
            body {
                font-family: 'Nunito';
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="relative flex items-top justify-center min-h-screen bg-red-700 dark:bg-red-900 sm:items-center sm:pt-0">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-center pt-8 sm:justify-start sm:pt-0">
                    <img src="{{ asset('images/allstar.jpeg') }}" alt="image">
                </div>

                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
                    <div class="grid grid-cols-1">
                        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center">
                                <!-- <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-8 h-8 text-gray-500"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                <div class="ml-4 text-lg leading-7 font-semibold">
                                    <p class="underline text-gray-900 dark:text-white">Selamat, Email berhasil di verifikasi</p>
                                </div> -->
                            </div>

                            <div class="mx-auto">
                                <div class="text-gray-600 dark:text-gray-400 text-lg">
                                    {{ $message }}
                                </div>
                                @if ($success == true)
                                <div class="mt-4">
                                    @if ($role == 'customer')
                                    <a href="{{env('MOBILE_APP_CUSTOMER_URL')}}" class="bg-gray-400 text-white py-2 px-4 rounded-lg hover:bg-gray-600">
                                        Login customer
                                    </a>
                                    @elseif ($role == 'driver')
                                    <a href="{{env('MOBILE_APP_DRIVER_URL')}}" class="bg-gray-400 text-white py-2 px-4 rounded-lg hover:bg-gray-600">
                                        Login driver
                                    </a>
                                    @else
                                    <a href="{{env('FRONTEND_URL')}}" class="bg-gray-400 text-white py-2 px-4 rounded-lg hover:bg-gray-600">
                                        Login {{ $role }}
                                    </a>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center mt-4 sm:items-center sm:justify-between">
                    <div class="text-center text-sm text-gray-500 sm:text-left">
                        <div class="flex items-center">
                            <!-- <svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor" class="-mt-px w-5 h-5 text-gray-400">
                                <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg> -->

                            <!-- <a href="https://laravel.bigcartel.com" class="ml-1 underline">
                                Shop
                            </a>

                            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="ml-4 -mt-px w-5 h-5 text-gray-400">
                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>

                            <a href="https://github.com/sponsors/taylorotwell" class="ml-1 underline">
                                Sponsor
                            </a> -->
                        </div>
                    </div>

                    <div class="ml-4 text-center text-sm text-gray-100 sm:text-right sm:ml-0">
                        Allstar CMS v1.0.0
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
