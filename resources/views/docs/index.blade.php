<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <div class="w-full flex flex-col bg-transparent">
            <div class="flex items-center justify-between">
                <nav aria-label="breadcrumb" class="w-1/3 space-y-2">
                    <ol class="flex w-full flex-wrap items-center rounded-md px-4 py-3">
                        <li class="flex cursor-pointer items-center text-lg/3 text-slate-600 transition-colors duration-300 hover:text-brown-700">
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            </a>
                            <span class="pointer-events-none mx-2 text-slate-600">/</span>
                        </li>
                        <li class="flex font-alkalami items-center text-lg/3 text-slate-500 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Documents</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-2/3 sm:w-full">
                    <h5 class="font-normal font-alkalami flex space-y-2 justify-end m-0 uppercase">Add Staff Documents</h5>
                </div>
            </div>
            <div>
                <form type="post" accept-charset="" action="" class="lg:gap-x-8 gap-x-2 flex w-full py-4">
                    @csrf

                    <div class="w-full flex flex-col lg:w-1/3">
                        <div class="w-full flex flex-col bg-brown-50 px-4 py-4 border border-brown-100 rounded-md">
                            <x-docs-sidebar activePage="{{ $active_page }}" />
                        </div>
                    </div>
                    <div class="w-full lg:w-2/3 px-4 py-2 bg-brown-50 border border-brown-100 rounded-lg">
                        <div class="flex flex-col items-start border-b border-b-brown-100">
                            <h2 class="text-lg font-alkalami font-semibold mb-0 text-slate-600">Upload Document</h2>
                            <p class="font-normal font-sans text-md text-slate-500 m-0">Upload docs for <small><em>staff name</em></small></p>
                        </div>
                        <div class="py-4 block">
                            <div class="form-input-area py-3 px-3 mt-3 gap-y-4">
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Document Type</label>
                                        <div class="relative">
                                            <select class="w-full bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="doc-type">
                                                <option value="">Select Option</option>
                                                <option value="Educational Certificate">Educational Certificates</option>
                                                <option value="Personal Certificates">Age Declaration/Certificate of Origin</option>
                                                <option value="Promotions">Promotions</option>
                                                <option value="Leave">Leave Approvals</option>
                                                <option value="Others">Husband</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Date of Birth</label>
                                        <div class="relative">
                                            <input type="date" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="date-of-birth" placeholder="YYYY/MM/DD" value="" />
                                        </div>
                                    </div>
                                </div>

                                <div class="py-3 px-4 flex items-center justify-end gap-2">
                                    <button type="submit" class="rounded-md bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                        Preview Uploaded
                                    </button>
                                    <button type="reset" class="rounded-md border border-brown-200 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-brown-600 hover:text-white hover:bg-brown-300 hover:border-gray-50 focus:text-white focus:bg-brown-300 focus:border-gray-50 active:border-gray-50 active:text-white active:bg-brown-300">
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
<script>
    $(function(){

    })
</script>

