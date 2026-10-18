<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <div class="w-full flex flex-col bg-transparent">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <nav aria-label="breadcrumb" class="w-full sm:w-1/3 space-y-2">
                    <ol class="flex w-full flex-wrap items-center gap-1.5 py-2 text-xs">
                        <li class="flex cursor-pointer items-center gap-1.5 text-base-content/60 transition-colors duration-300 hover:text-[#2E7D32]">
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            </a>
                            <span class="pointer-events-none text-base-content/30">/</span>
                        </li>
                        <li class="flex items-center text-base-content/60 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Documents</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-full sm:w-2/3">
                    <h5 class="font-alkalami text-sm font-semibold uppercase tracking-wide flex justify-end m-0">Add Staff Documents</h5>
                </div>
            </div>
            <div>
                <form type="post" accept-charset="" action="" class="flex w-full flex-col gap-y-4 py-4 lg:flex-row lg:gap-x-8 lg:gap-y-0">
                    @csrf

                    <div class="w-full flex flex-col lg:w-1/3">
                        <div class="w-full flex flex-col bg-white px-4 py-4 border border-base-300 rounded-md">
                            <x-docs-sidebar activePage="{{ $active_page }}" :uuid="$uri_segments[3]" />
                        </div>
                    </div>
                    <div class="w-full lg:w-2/3 px-4 py-2 bg-white border border-base-300 rounded-lg">
                        <div class="flex flex-col items-start border-b border-b-brown-100">
                            <h2 class="font-alkalami text-base font-semibold mb-0 text-base-content/80">Upload Document</h2>
                            <p class="font-sans text-sm font-normal text-base-content/60 m-0">Upload docs for <small><em>staff name</em></small></p>
                        </div>
                        <div class="py-4 block bg-contain bg-right-top bg-no-repeat" style="background-image: url('{{Vite::asset('resources/images/bg-state-empty.png')}}');background-size: 300px">
                            <div class="form-input-area py-3 px-3 mt-3 gap-y-4">
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full">
                                        <label class="font-semibold font-sans text-sm text-base-content">Document Type</label>
                                        <div class="relative">
                                            <select class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="doc-type">
                                                <option value="">Select Option</option>
                                                <option value="Educational Certificate">Educational Certificates</option>
                                                <option value="Personal Certificates">Age Declaration/Certificate of Origin</option>
                                                <option value="Promotions">Promotions</option>
                                                <option value="Leave">Leave Approvals</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div id="cloneable" class="w-full relative mb-3">
                                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                        <div class="w-full">
                                            <label class="font-semibold font-sans text-sm text-base-content">Pick File</label>
                                            <div class="relative">
                                                <input
                                                    name="file"
                                                    type="file"
                                                    placeholder="Pick Document"
                                                    class="file-input file-input-bordered w-full max-w-x bg-white text-xs"
                                                     accept="application/pdf, image/*" size=""/>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                    <path d="M15 10l-4 4l6 6l4 -16l-18 7l4 2l2 6l3 -4"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="clonesContainer" class="flex flex-col items-start gap-x-2 w-full relative"></div>

                                <div class="flex items-center justify-between">
                                    <div class="mt-3 mb-3 inline-flex items-center">
                                        <label class="flex items-center cursor-pointer relative peer-checked:bg-primary peer-checked:text-primary-content" for="check-2">
                                            <input id="check-2" type="checkbox" class="peer h-5 w-5 cursor-pointer transition-all appearance-none rounded shadow hover:shadow-md border border-base-300 checked:bg-primary checked:border-primary"/>
                                            <span class="absolute text-primary-content opacity-0 peer-checked:opacity-100 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"
                                                   stroke="currentColor" stroke-width="1">
                                                    <path fill-rule="evenodd"
                                                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                      clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </label>
                                        <label class="cursor-pointer ml-2 text-base-content/80 text-sm" for="check-2">Select multiple files at once</label>
                                    </div>
                                    <button id="addFileBtn" type="button" class="hidden btn-sm btn btn-sm btn-cta-outline px-4">
                                        Add Image
                                    </button>
                                </div>
{{--                                <div class="py-3 px-4 flex flex-wrap items-center justify-end gap-2">--}}
{{--                                    <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">--}}
{{--                                        Preview Uploaded--}}
{{--                                    </button>--}}
{{--                                    <button type="reset" class="btn btn-sm btn-cta-outline px-4">--}}
{{--                                        Reset--}}
{{--                                    </button>--}}
{{--                                </div>--}}
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

        $('#check-2').change(function(){
            $('#addFileBtn').toggleClass('hidden');

            if(!$(this).is(':checked')) {
                $('#clonesContainer').empty();
                counter = 1;
            }
        })

        let classes = 'py-3 px-3 border border-base-300 rounded-lg';
        let btn = `<button type="button" class="removeClones rounded-full w-6 h-6 absolute top-1/2 bottom-2 -right-3 text-center bg-error text-error-content border border-error hover:brightness-95 focus:brightness-95 active:brightness-95 shadow-lg">x</button>`;

        // Click event for the clone button
        $("#addFileBtn").click(function(){
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

