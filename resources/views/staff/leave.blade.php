<x-dashboard pageTitle="{{ $page_title }}">
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
                        <li class="flex items-center text-lg/3 text-slate-500 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Education</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-2/3 sm:w-full">
                    <h5 class="font-semibold font-sans flex space-y-2 justify-end m-0 uppercase">Staff Management - Add New Entry</h5>
                </div>
            </div>
            <div>
                <form type="post" accept-charset="" action="" class="lg:gap-x-8 gap-x-2 flex w-full py-4">
                    @csrf

                    <input type="hidden" name="form-type" value="educational-progress" />

                    <div class="w-full flex flex-col lg:w-1/3">
                        <a href="#" class="flex items-center justify-start space-x-3 py-2 px-4 text-center text-slate-500 text-xs hover:text-slate-900 hover:underline transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                <path d="M3 17l9 5l9 -5v-3l-9 5l-9 -5v-3l9 5l9 -5v-3l-9 5l-9 -5l9 -5l5.418 3.01"></path>
                            </svg>
                            <span class="">Upload Bulk</span>
                        </a>
                        <div class="w-full flex flex-col bg-brown-50 px-4 py-4 border border-brown-100 rounded-md">
                            <x-staff-sidebar activePage="{{ $active_page }}" />
                        </div>
                    </div>
                    <div class="w-full lg:w-2/3 px-4 py-2 bg-brown-50 border border-brown-100 rounded-lg">
                        <div class="flex flex-col items-start border-b border-b-brown-100">
                            <h2 class="text-lg font-semibold mb-0 text-slate-600">Promotions</h2>
                            <p class="font-normal text-md text-slate-500 m-0">Staff record of promotions</p>
                        </div>
                        <div class="py-4 block">
                            <div class="form-input-area py-3 px-3 mt-3 gap-y-4">
                                <div class="w-full">
                                    <div id="cloneable" class="w-full relative mb-3">
                                        <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                            <div class="w-full">
                                                <label class="cloneableInstitutionTypeLabel font-semibold font-sans text-sm text-slate-800">Designation</label>
                                                <div class="relative">
                                                    <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="designation[]" placeholder="E.g Admin. Officer I" value="" />
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
                                            <div class="w-full max-w-sm">
                                                <label class="cloneableInstitutionTypeLabel font-semibold font-sans text-sm text-slate-800">Grade Level</label>
                                                <div class="relative">
                                                    <input type="number" step="1" min="1" max="17" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="grade-level[]" placeholder="Grade Level" value="" />
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                        <path d="M9 18l3 3l3 -3"></path>
                                                        <path d="M12 15v6"></path>
                                                        <path d="M15 6l-3 -3l-3 3"></path>
                                                        <path d="M12 3v6"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="w-full max-w-sm">
                                                <label class="cloneableInstitutionTypeLabel font-semibold font-sans text-sm text-slate-800">Step</label>
                                                <div class="relative">
                                                    <input type="number" step="1" min="1" max="17" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="step[]" placeholder="Step" value="" />
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                        <path d="M9 18l3 3l3 -3"></path>
                                                        <path d="M12 15v6"></path>
                                                        <path d="M15 6l-3 -3l-3 3"></path>
                                                        <path d="M12 3v6"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-full">
                                            <label class="cloneableInstitutionTypeLabel font-semibold font-sans text-sm text-slate-800">Effective From</label>
                                            <div class="relative">
                                                <input type="date" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="grade-level[]" placeholder="E.g Admin. Officer I" value="" />
                                            </div>
                                        </div>
                                    </div>

                                    <div id="clonesContainer" class="flex flex-col items-start gap-x-2 w-full relative"></div>
                                </div>
                                <div class="py-3 px-4 flex items-center justify-between">
                                    <button id="addInstitution" type="button" class="rounded-md border border-slate-500 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-400 hover:border-slate-600 focus:text-white focus:bg-slate-400 focus:border-slate-600 active:border-slate-600 active:text-white active:bg-slate-400">
                                        Add Promotion
                                    </button>
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="submit" class="rounded-md bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                            Save & Continuer
                                        </button>
                                        <button type="reset" class="rounded-md border border-brown-200 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-brown-600 hover:text-white hover:bg-brown-300 hover:border-gray-50 focus:text-white focus:bg-brown-300 focus:border-gray-50 active:border-gray-50 active:text-white active:bg-brown-300">
                                            Reset
                                        </button>
                                    </div>
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
        let counter = 1;
        let classes = 'py-3 px-3 border border-brown-200 rounded-lg';
        let btn = `<button type="button" class="removeClones rounded-full w-6 h-6 absolute top-1/2 bottom-2 -right-3 text-center bg-red-300 text-white border border-red-400 hover:bg-red-400 focus:bg-red-400 active:bg-red-400 hover:border-red-500 focus:border-red-500 active:border-red-500 shadow-lg">x</button>`;

        // Click event for the clone button
        $("#addInstitution").click(function(){
            // Increment the counter
            counter++;

            // Clone the container and update its ID
            let newContainer = $("#cloneable").clone().attr("id", "cloneable" + counter);
            $(newContainer).append(btn);
            $(newContainer).find('label').append(' ' + counter);
            $(newContainer).addClass(classes)
            // Append the cloned container to the desired area
            $("#clonesContainer").append(newContainer);
        });

        $("#clonesContainer").on("click", ".removeClones", function(){
            $(this).parent().remove();
        });
    })
</script>
