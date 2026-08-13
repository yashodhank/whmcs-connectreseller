$(document).ready(function () {

    $(".domain-table").show();
    $('.alldomain-box').append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
    dataTableFun('Get Domain Sync');
    $('.alldomain-box').find("i.fa-spinner").remove();
    const secureCall = (data = {}, method = "GET", url = '') => {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function (response) {
                    resolve(response);
                },
                error: function (error) {
                    reject(error);
                }
            });
        });
    }

    $(document).on('change', "input[name='checkbox[]'] ,#selectAll ", function () {
        var checkedCount = $("input[name='checkbox[]']:checked").length;
        $('.create-domain').text('Import TLDs ' + checkedCount);
    });

    // $(document).on("click", ".create-domain", async function () {
    //     try {
    //         $this = $(this);
    //         $this.prop('disabled', true);
    //         $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
    //         var formData = '';
    //         var formData = $('.all-ssl-domain').serialize();

    //         $("#progressBar-container").show();
    //         $("#progressBar").css("width", "30%");
    //         $("#showProcessBarValue").html('30%');

    //         setTimeout(() => {$("#progressBar").css("width", "60%")
    //             $("#showProcessBarValue").html('60%');
    //         }, 500);
            

    //         let response = await secureCall({
    //             "ajaxcall": true,
    //             "ajaxaction": "Create Domain",
    //             'data': formData
    //         }, 'POST');

    //         setTimeout(() => {$("#progressBar").css("width", "60%")
    //             $("#showProcessBarValue").html('60%');
    //         }, 500);

    //         $("#progressBar").css("width", "90%");
    //         $("#showProcessBarValue").html('90%');

    //         $this.find("i.fa-spinner").remove();
    //         const parsedResponse = JSON.parse(response);

    //         if (parsedResponse.status === true) {
    //             jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
    //             domainsync();
    //         } else {
    //             jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
    //         }
    //         $("#progress-bar").css("width", "100%");
    //         setTimeout(() => {
    //             $("#progressBar-container").fadeOut(400, function () {
    //                 $("#progressBar").css("width", "0%");
    //                 $("#showProcessBarValue").html('0%');
    //             });
    //         }, 700);
    //         $this.prop('disabled', false);
    //     } catch (error) {
    //         console.error(error);
    //     }
    // });

    // $(document).on("click", ".domain_sync", function () {
    //     try {
    //         $(".domain-table").show();
    //         $('.alldomain-box').append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');

    //         dataTableFun('Get Domain Sync');
    //         $('.alldomain-box').find("i.fa-spinner").remove();
    //     } catch (error) {
    //         console.error(error)
    //     }
    // });


    $(document).on("click", ".create-domain", async function () {
        try {
            let $this = $(this);
            $this.prop('disabled', true);
            $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
     
            // Show progress bar
            $(".progress-bar-container").show();
            updateProgress(10); // Start at 10%
     
            var formData = $('.all-ssl-domain').serialize();
            
            console.log(formData);
            // Simulate progress in steps while waiting for response
            updateProgress(30);
            setTimeout(() => updateProgress(50), 300);
            setTimeout(() => updateProgress(70), 600);
     
            let response = await secureCall({
                "ajaxcall": true,
                "ajaxaction": "Create Domain",
                'data': formData
            }, 'POST');
     
            updateProgress(90);
     
            $this.find("i.fa-spinner").remove();
            const parsedResponse = JSON.parse(response);
     
            if (parsedResponse.status === true) {
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
                $(".domain_sync").click();
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
            }
     
            // Finish at 100%
            updateProgress(100);
     
            // Hide after short delay
            setTimeout(() => {
                $(".progress-bar-container").fadeOut(400, function () {
                    updateProgress(0);
                });
            }, 800);
     
            $this.prop('disabled', false);
        } catch (error) {
            console.error(error);
            $(".progress-bar-container").fadeOut(400, function () {
                updateProgress(0);
            });
        }
    });
     
    // Helper to update progress bar and percent label
    function updateProgress(percent) {
        $(".progress-bar").css("width", percent + "%");
        $(".progress-percent").text(percent + "%");
    }

    function domainsync() {
        try {
            $(".domain-table").show();
            $('.alldomain-box').append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');

            dataTableFun('Get Domain Sync');
            $('.alldomain-box').find("i.fa-spinner").remove();
            $('.create-domain').text('Import TLDs');
        } catch (error) {
            console.error(error)
        }
    }


    $(document).on("click", ".manual_sync", async function () {
        try {
            $this = $(this);
            $this.prop('disabled', true);
            $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            let response = await secureCall({ "ajaxcall": true, "ajaxaction": "manual Sync TLDs", }, 'POST');
            const parsedResponse = JSON.parse(response);
            if (parsedResponse.status === true) {
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
                $this.find("i.fa-spinner").remove();
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
            }
            $this.prop('disabled', false);
        } catch (error) {
            console.error(error)
        }
    });

    $(document).on("click", "#selectAll", function (e) {
        var isChecked = $(this).prop('checked');
        $("input[name='checkbox[]']").prop('checked', isChecked);
    });

    $(window).on("load", function () {
        // dataTableFun('default');
        enableDisableDatatable('Enable/Disable TLD List');
    });

    function dataTableFun(ajaxCallFor = '') {
        $("#domainTable").dataTable().fnDestroy();
        $('#domainTable').DataTable({
            processing: true,
            searching: true,
            order: [[0, "desc"]],
            serverSide: true,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            pageLength: 100,
            ajax: {
                url: '', // Your API endpoint
                type: 'POST',
                data: function (d) {

                    d.ajaxcall = true;
                    d.ajaxaction = ajaxCallFor;
                    // Add pagination parameters
                    d.start = d.start || 0;
                    d.length = d.length || 10;
                },
                dataSrc: function (json) {

                    if (json.status === false) {
                        jQuery.growl.error({
                            title: "Error",
                            message: json.message || "An unknown error occurred",
                            duration: 3000
                        });
                    }

                    return json.data || [];
                },
                error: function (xhr, error, thrown) {
                    console.error("AJAX error:", xhr.responseText);  // Log the error message
                }
            },
            columns: [
                { data: 'checkbox', orderable: false },
                { data: 'existtld' },
                { data: 'tld' },
                { data: 'registration_price' },
                { data: 'renewal_price' },
                { data: 'transfer_price' },
                { data: 'currency_code' },
                { data: 'min_period' },
                { data: 'max_period' }
            ],
            columnDefs: [
                {
                    targets: [0],
                    visible: true,
                    searchable: true
                }
            ],
            language: {
                infoFiltered: "",
                emptyTable: "No data available in table"
            }
        });
    }

    function enableDisableDatatable(ajaxCallFor = '') {
        $('#tldTable').DataTable({
            processing: true,
            searching: true,
            order: [[0, "desc"]],
            serverSide: true,
            pageLength: 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            ajax: {
                url: '', // Your API endpoint
                type: 'POST',
                data: function (d) {
                    d.ajaxcall = true;
                    d.ajaxaction = ajaxCallFor;
                    d.start = d.start || 0;
                    d.length = d.length === -1 ? 100000 : d.length;
                },
                dataSrc: function (json) {
                    return json.data || [];
                },
                error: function (xhr, error, thrown) {
                    console.error("AJAX error:", xhr.responseText);  // Log the error message
                }
            },
            columns: [
                // { data: 'domain_id' },
                { data: 'extension' },
                { data: 'status' },
            ],
            columnDefs: [
                {
                    targets: [0],
                    visible: true,
                    searchable: true
                }
            ],
            language: {
                infoFiltered: "",
                emptyTable: "No data available in table"
            }
        });
    }

    $(document).on("click", ".toggle-checkbox", async function () {
        try {
            $this = $(this);
            $this.prop('disabled', true);

            var tld_id = $this.attr('tld_id');

            const NEWSTATUS = $this.data('status') == 'on' ? 'off' : 'on';
            $this.after('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            let result = await secureCall({ "ajaxcall": true, "ajaxaction": "Enable/Disable TLD", 'tld': tld_id, 'status': NEWSTATUS }, 'POST');

            $this.next("i.fa-spinner").remove();
            var parsedResponse = JSON.parse(result);

            if (parsedResponse.status === true) {
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
            }

            $this.prop('disabled', false);
        } catch (error) {
            console.error(error)
        }
    });

    $(document).on("click", ".toggle-checkboxs", async function () {

        if($(this).prop('checked') == true){
            $('#enabletld').submit();
        }else{
            $('#disabletld').submit();
        }
    });

    $(document).ready(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });

});
