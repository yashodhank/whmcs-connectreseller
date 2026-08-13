$(document).ready(function () {
    var addonCfg = window.ConnectResellerAddon || {};
    var moduleUrl = addonCfg.moduleLink || window.location.href.split("#")[0];
    var csrfToken = addonCfg.token || "";

    function withCsrf(data) {
        data = data || {};
        if (csrfToken) {
            data.token = csrfToken;
        }
        return data;
    }

    function hideProcessing($table) {
        var $wrap = $table.closest(".dataTables_wrapper");
        $wrap.find("div.dataTables_processing").hide();
        try {
            var api = $table.DataTable();
            if (api && typeof api.processing === "function") {
                api.processing(false);
            }
        } catch (e) {
            // table may not be initialised yet
        }
    }

    function growlError(message) {
        if (jQuery.growl && jQuery.growl.error) {
            jQuery.growl.error({
                title: "Error",
                message: message || "Request failed",
                duration: 4000
            });
        }
    }

    function growlNotice(message) {
        if (jQuery.growl && jQuery.growl.notice) {
            jQuery.growl.notice({
                title: "Success",
                message: message || "Done",
                duration: 3000
            });
        }
    }

    function parseJsonSafe(response) {
        if (response && typeof response === "object") {
            return response;
        }
        if (typeof response !== "string" || response === "") {
            return null;
        }
        try {
            return JSON.parse(response);
        } catch (e) {
            return null;
        }
    }

    function ajaxErrorMessage(xhr) {
        var text = (xhr && xhr.responseText) ? String(xhr.responseText) : "";
        var trimmed = text.replace(/^\uFEFF/, "").replace(/^\s+/, "");
        // Successful Sync payloads embed HTML form controls inside JSON strings.
        // Only treat real HTML documents / PHP error pages as unexpected.
        if (trimmed.charAt(0) === "{" || trimmed.charAt(0) === "[") {
            var parsedJson = parseJsonSafe(trimmed);
            if (parsedJson && parsedJson.message) {
                return parsedJson.message;
            }
        }
        if (/^</.test(trimmed) || /<\s*(!doctype|html|body|br|b|title|pre)\b/i.test(trimmed)) {
            return "Admin returned HTML instead of JSON (session/CSRF/PHP error). Reload the page and check module logs.";
        }
        var parsed = parseJsonSafe(text);
        if (parsed && parsed.message) {
            return parsed.message;
        }
        return text || "Request failed";
    }

    var secureCall = function (data, method, url) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: url || moduleUrl,
                method: method || "GET",
                data: withCsrf(data),
                success: function (response) {
                    resolve(response);
                },
                error: function (error) {
                    reject(error);
                }
            });
        });
    };

    var hasDomainTable = $("#domainTable").length > 0;
    var hasTldTable = $("#tldTable").length > 0;
    var domainTableApi = null;
    var tldTableApi = null;

    function selectedTldCount() {
        return $("input[name='checkbox[]']:checked").length;
    }

    function updateImportButtonState() {
        var count = selectedTldCount();
        var $btn = $(".create-domain");
        var label = count > 0 ? ("Import TLDs " + count) : "Import TLDs";
        $btn.each(function () {
            var $el = $(this);
            var $spin = $el.find("i.fa-spinner");
            $el.contents().filter(function () {
                return this.nodeType === 3;
            }).remove();
            if ($spin.length) {
                $el.prepend(document.createTextNode(label + " "));
            } else {
                $el.text(label);
            }
            if (!$el.data("busy")) {
                $el.prop("disabled", count === 0);
            }
        });
    }

    function setImportBusy($btn, busy) {
        $btn.data("busy", busy ? 1 : 0);
        if (busy) {
            $btn.prop("disabled", true);
            if (!$btn.find("i.fa-spinner").length) {
                $btn.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            }
        } else {
            $btn.find("i.fa-spinner").remove();
            updateImportButtonState();
        }
    }

    function reloadDomainTable() {
        if (domainTableApi) {
            domainTableApi.ajax.reload(null, false);
            return;
        }
        if (hasDomainTable) {
            dataTableFun("Get Domain Sync");
        }
    }

    function dataTableFun(ajaxCallFor) {
        if (!hasDomainTable) {
            return;
        }

        if ($.fn.DataTable.isDataTable("#domainTable")) {
            $("#domainTable").DataTable().clear().destroy();
        }

        domainTableApi = $("#domainTable").DataTable({
            processing: true,
            searching: true,
            order: [[2, "asc"]],
            serverSide: true,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            pageLength: 100,
            ajax: {
                url: moduleUrl,
                type: "POST",
                data: function (d) {
                    d.ajaxcall = true;
                    d.ajaxaction = ajaxCallFor;
                    d.start = d.start || 0;
                    d.length = d.length || 10;
                    if (csrfToken) {
                        d.token = csrfToken;
                    }
                },
                dataSrc: function (json) {
                    if (!json || typeof json !== "object") {
                        hideProcessing($("#domainTable"));
                        growlError("Invalid response from Sync TLDs.");
                        return [];
                    }
                    if (json.status === false) {
                        hideProcessing($("#domainTable"));
                        growlError(json.message || "An unknown error occurred");
                    }
                    return json.data || [];
                },
                error: function (xhr) {
                    hideProcessing($("#domainTable"));
                    growlError(ajaxErrorMessage(xhr));
                }
            },
            columns: [
                { data: "checkbox", orderable: false },
                { data: "existtld" },
                { data: "tld" },
                { data: "registration_price" },
                { data: "renewal_price" },
                { data: "transfer_price" },
                { data: "currency_code" },
                { data: "min_period" },
                { data: "max_period" }
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
                emptyTable: "No TLDs returned from the API",
                zeroRecords: "No matching TLDs",
                processing: "Loading TLDs…"
            },
            drawCallback: function () {
                updateImportButtonState();
            }
        });
    }

    function enableDisableDatatable(ajaxCallFor) {
        if (!hasTldTable) {
            return;
        }

        if ($.fn.DataTable.isDataTable("#tldTable")) {
            $("#tldTable").DataTable().clear().destroy();
        }

        tldTableApi = $("#tldTable").DataTable({
            processing: true,
            searching: true,
            order: [[0, "asc"]],
            serverSide: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            ajax: {
                url: moduleUrl,
                type: "POST",
                data: function (d) {
                    d.ajaxcall = true;
                    d.ajaxaction = ajaxCallFor;
                    d.start = d.start || 0;
                    d.length = d.length === -1 ? 100000 : d.length;
                    if (csrfToken) {
                        d.token = csrfToken;
                    }
                },
                dataSrc: function (json) {
                    if (!json || typeof json !== "object") {
                        hideProcessing($("#tldTable"));
                        growlError("Invalid response from Automation.");
                        return [];
                    }
                    if (json.status === false) {
                        hideProcessing($("#tldTable"));
                        growlError(json.message || "An unknown error occurred");
                    } else if (json.message && (!json.data || !json.data.length)) {
                        // empty-state guidance from server (import first)
                        var $note = $(".automation-empty-note");
                        if ($note.length) {
                            $note.removeClass("hidden").show();
                        }
                    }
                    return json.data || [];
                },
                error: function (xhr) {
                    hideProcessing($("#tldTable"));
                    growlError(ajaxErrorMessage(xhr));
                }
            },
            columns: [
                { data: "extension" },
                { data: "status" }
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
                emptyTable: "No TLDs yet — import them on the Sync TLDs tab first",
                zeroRecords: "No matching TLDs",
                processing: "Loading…"
            },
            initComplete: function () {
                var $bulk = $(".tld-bulk-toggle");
                if ($bulk.length) {
                    var $length = $("#tldTable_wrapper .dataTables_length");
                    if ($length.length && !$bulk.data("moved")) {
                        $bulk.insertAfter($length).addClass("tld-bulk-toggle--toolbar").data("moved", 1);
                    }
                }
            }
        });
    }

    if (hasDomainTable) {
        $(".domain-table").show();
        dataTableFun("Get Domain Sync");
        updateImportButtonState();
    }

    if (hasTldTable) {
        enableDisableDatatable("Enable/Disable TLD List");
    }

    $(document).on("change", "input[name='checkbox[]'], #selectAll", function () {
        updateImportButtonState();
    });

    $(document).on("click", ".create-domain", async function () {
        var $this = $(this);
        if ($this.data("busy") || selectedTldCount() === 0) {
            return;
        }

        try {
            setImportBusy($this, true);
            $(".progress-bar-container").show();
            updateProgress(10);

            var formData = $(".all-ssl-domain").serialize();
            updateProgress(30);
            setTimeout(function () {
                updateProgress(50);
            }, 300);
            setTimeout(function () {
                updateProgress(70);
            }, 600);

            var response = await secureCall({
                ajaxcall: true,
                ajaxaction: "Create Domain",
                data: formData
            }, "POST");

            updateProgress(90);

            var parsedResponse = parseJsonSafe(response);
            if (!parsedResponse) {
                growlError("Import returned a non-JSON response.");
            } else if (parsedResponse.status === true) {
                growlNotice(parsedResponse.message);
                $("input[name='checkbox[]'], #selectAll").prop("checked", false);
                reloadDomainTable();
            } else {
                growlError(parsedResponse.message || "Import failed");
            }

            updateProgress(100);
            setTimeout(function () {
                $(".progress-bar-container").fadeOut(400, function () {
                    updateProgress(0);
                });
            }, 800);
        } catch (error) {
            console.error(error);
            growlError(ajaxErrorMessage(error));
            $(".progress-bar-container").fadeOut(400, function () {
                updateProgress(0);
            });
        } finally {
            setImportBusy($this, false);
        }
    });

    function updateProgress(percent) {
        $(".progress-bar").css("width", percent + "%");
        $(".progress-percent").text(percent + "%");
    }

    $(document).on("click", ".manual_sync", async function () {
        var $this = $(this);
        try {
            $this.prop("disabled", true);
            $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            var response = await secureCall({
                ajaxcall: true,
                ajaxaction: "manual Sync TLDs"
            }, "POST");
            var parsedResponse = parseJsonSafe(response);
            if (!parsedResponse) {
                growlError("Manual sync returned a non-JSON response.");
            } else if (parsedResponse.status === true) {
                growlNotice(parsedResponse.message);
            } else {
                growlError(parsedResponse.message || "Sync failed");
            }
        } catch (error) {
            console.error(error);
            growlError(ajaxErrorMessage(error));
        } finally {
            $this.find("i.fa-spinner").remove();
            $this.prop("disabled", false);
        }
    });

    $(document).on("click", "#selectAll", function () {
        var isChecked = $(this).prop("checked");
        $("input[name='checkbox[]']").prop("checked", isChecked);
        updateImportButtonState();
    });

    $(document).on("click", ".toggle-checkbox", async function () {
        var $this = $(this);
        try {
            $this.prop("disabled", true);
            var tldId = $this.attr("tld_id");
            var newStatus = $this.data("status") == "on" ? "off" : "on";
            $this.after('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');

            var result = await secureCall({
                ajaxcall: true,
                ajaxaction: "Enable/Disable TLD",
                tld: tldId,
                status: newStatus
            }, "POST");

            $this.next("i.fa-spinner").remove();
            var parsedResponse = parseJsonSafe(result);
            if (!parsedResponse) {
                growlError("Toggle returned a non-JSON response.");
            } else if (parsedResponse.status === true) {
                $this.data("status", newStatus);
                growlNotice(parsedResponse.message);
            } else {
                growlError(parsedResponse.message || "Update failed");
                $this.prop("checked", newStatus !== "on");
            }
        } catch (error) {
            console.error(error);
            growlError(ajaxErrorMessage(error));
        } finally {
            $this.prop("disabled", false);
        }
    });

    $(document).on("click", ".toggle-checkboxs", function () {
        if ($(this).prop("checked") === true) {
            $("#enabletld").submit();
        } else {
            $("#disabletld").submit();
        }
    });

    if ($("[data-bs-toggle='tooltip']").length) {
        $("[data-bs-toggle='tooltip']").tooltip();
    }
});
