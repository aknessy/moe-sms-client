<x-layout title="{{ $page_title }}">
    <x-slot name="slot">
        <div class="flex h-full w-full items-center justify-center px-6 py-12 lg:px-8">
            <div class="w-80 flex-col items-center">
                <div class="sm:mx-auto sm:w-full sm:max-w-sm mb-2">
                    <img class="mx-auto h-20 w-auto" src="{{ Vite::asset('resources/images/app_favicon.png') }}" alt="">
                    <h2 class="mb-0 text-center text-lg font-bold block mt-4 text-slate-800 uppercase">Ministry of Establishment</h2>
                    <h3 class="text-center tracking-tight font-semibold text-slate-600">System Login</h3>
                </div>
                <div class="w-full bg-brown-50 rounded-lg border border-brown-100 shadow-sm">
                    <div class="bg-brown-100 border-b border-b-brown-100 rounded-t-lg px-4 py-4">
                        <p class="text-center text-brown-400">Fill-in your credentials to proceed</p>
                    </div>
                    <div class="mt-0  sm:mx-auto sm:w-full sm:max-w-sm px-4 py-5">
                        <form class="space-y-6" action="#" method="POST">
                            <div>
                                <label for="userhandle" class="font-semibold font-sans text-sm text-slate-800 mb-0">Username</label>
                                <div class="w-full max-w-sm min-w-[200px]">
                                    <div class="relative">
                                        <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="userhandle" value="{{ old('userhandle') }}" required />

                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                            <path d="M4.5 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM14.25 8.625a3.375 3.375 0 1 1 6.75 0 3.375 3.375 0 0 1-6.75 0ZM1.5 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM17.25 19.128l-.001.144a2.25 2.25 0 0 1-.233.96 10.088 10.088 0 0 0 5.06-1.01.75.75 0 0 0 .42-.643 4.875 4.875 0 0 0-6.957-4.611 8.586 8.586 0 0 1 1.71 5.157v.003Z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="password" class="font-semibold font-sans text-sm text-slate-800">Password</label>
                                <div class="w-full max-w-sm min-w-[200px]">
                                    <div class="relative">
                                        <input type="password" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="secrethash" required />
                                        <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                            <path d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -20 0c0 -5.523 4.477 -10 10 -10zm2 5a3 3 0 0 0 -2.98 2.65l-.015 .174l-.005 .176l.005 .176c.019 .319 .087 .624 .197 .908l.09 .209l-3.5 3.5l-.082 .094a1 1 0 0 0 0 1.226l.083 .094l1.5 1.5l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-.792 -.793l.585 -.585l.793 .792l.094 .083a1 1 0 0 0 1.403 -1.403l-.083 -.094l-.792 -.793l.792 -.792a3 3 0 1 0 1.293 -5.708zm0 2a1 1 0 1 1 0 2a1 1 0 0 1 0 -2z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-center gap-3 w-full">
                                <button type="reset" class="rounded-md border border-brown-200 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-brown-600 hover:text-white hover:bg-brown-300 hover:border-gray-50 focus:text-white focus:bg-brown-300 focus:border-gray-50 active:border-gray-50 active:text-white active:bg-brown-300">
                                    Reset
                                </button>
                                <button type="submit" class="rounded-md bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <x-footer appVersion="{{ $app_version }}" />
            </div>
        </div>
    </x-slot>
</x-layout>
