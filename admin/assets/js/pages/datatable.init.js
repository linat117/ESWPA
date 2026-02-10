$(document).ready(function () {
    "use strict";

    // Basic datatable with custom compact controls
    $("#basic-datatable").DataTable({
        keys: !0,
        language: {
            paginate: {
                previous: "<i class='ri-arrow-left-s-line'>",
                next: "<i class='ri-arrow-right-s-line'>"
            }
        },
        drawCallback: function () {
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");

            var wrapper = $("#basic-datatable_wrapper");

            // Length / "show entries" control
            var length = wrapper.find(".dataTables_length");
            length.addClass("d-flex align-items-center mb-2 mb-md-0");
            var lengthLabel = length.find("label");
            var lengthSelect = length.find("select");
            lengthSelect
                .addClass("form-select form-select-sm")
                .css("height", "32px");

            // Rebuild label as: "Show" + compact select (only once)
            if (!lengthLabel.find(".dt-length-text").length) {
                // Clear existing text nodes
                lengthLabel.contents().filter(function () {
                    return this.nodeType === 3;
                }).remove();

                var spanText = $('<span class="dt-length-text me-1">Show</span>');
                spanText.css({
                    "display": "inline-flex",
                    "align-items": "center",
                    "height": "32px"
                });
                lengthLabel.prepend(spanText);
            }

            // Search control
            var filter = wrapper.find(".dataTables_filter");
            filter.addClass("mb-2 mb-md-0");
            var filterLabel = filter.find("label");

            // Remove "Search:" text
            filterLabel.contents().filter(function () {
                return this.nodeType === 3;
            }).remove();

            var searchInput = filter.find("input");

            // Wrap search input in input-group with dark-blue button (only once)
            if (!searchInput.parent().hasClass("input-group")) {
                searchInput
                    .addClass("form-control form-control-sm")
                    .attr("placeholder", "Search...")
                    .css({
                        "border-top-right-radius": "0",
                        "border-bottom-right-radius": "0",
                        "margin-left": "0", // remove default DataTables gap between label and input
                        "height": "32px"
                    });

                var group = $('<div class="input-group input-group-sm"></div>');
                group.css({
                    "display": "flex",
                    "flex-wrap": "nowrap",
                    "max-width": "200px",
                    "height": "32px",
                    "align-items": "center",
                    "margin-top" : "-16px"
                });
                searchInput.appendTo(group);

                var btn = $('<button class="btn" type="button"><i class="ri-search-line"></i></button>');
                btn.css({
                    "background-color": "#0d47a1",
                    "border-color": "#0d47a1",
                    "color": "#ffffff",
                    "border-top-left-radius": "0",
                    "border-bottom-left-radius": "0",
                    "width": "auto",
                    "flex": "0 0 auto",
                    "height": "32px",
                    "display": "flex",
                    "align-items": "center",
                    "justify-content": "center",
                    "padding": "0 0.75rem",
                    "margin-top" : "9px"
                });

                btn.on("click", function () {
                    // Trigger DataTables search on button click
                    searchInput.trigger("keyup");
                });

                group.append(btn);

                // Wrap search group + "Show" dropdown together so they are inline
                var controls = $('<div class="d-flex align-items-center gap-2"></div>');
                controls.append(group);

                length
                    .removeClass("mb-2 mb-md-0")
                    .css({
                        "margin-left": "0",
                        "display": "inline-flex",
                        "align-items": "center"
                    });

                lengthLabel
                    .addClass("mb-0")
                    .css({
                        "margin-bottom": "0",
                        "display": "flex",
                        "align-items": "center"
                    });

                controls.append(length);
                filter.empty().append(controls);
            }

            // Put controls row in a compact flex layout
            wrapper.find(".row:eq(0)").addClass("d-flex flex-wrap align-items-center gap-2");
        }
    });

    var a = $("#datatable-buttons").DataTable({
        lengthChange: !1,
        buttons: ["copy", "print"],
        language: {
            paginate: {
                previous: "<i class='ri-arrow-left-s-line'>",
                next: "<i class='ri-arrow-right-s-line'>"
            }
        },
        drawCallback: function () {
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
        }
    });

    $("#selection-datatable").DataTable({
        select: {
            style: "multi"
        },
        language: {
            paginate: {
                previous: "<i class='ri-arrow-left-s-line'>",
                next: "<i class='ri-arrow-right-s-line'>"
            }
        },
        drawCallback: function () {
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
        }
    }),
        a.buttons().container().appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)"),
        $("#alternative-page-datatable").DataTable({
            pagingType: "full_numbers",
            drawCallback: function () {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            }
        }),
        $("#scroll-vertical-datatable").DataTable({
            scrollY: "350px",
            scrollCollapse: !0,
            paging: !1,
            language: {
                paginate: {
                    previous: "<i class='ri-arrow-left-s-line'>",
                    next: "<i class='ri-arrow-right-s-line'>"
                }
            },
            drawCallback: function () {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            }
        }),
        $("#scroll-horizontal-datatable").DataTable({
            scrollX: !0,
            language: {
                paginate: {
                    previous: "<i class='ri-arrow-left-s-line'>",
                    next: "<i class='ri-arrow-right-s-line'>"
                }
            },
            drawCallback: function () {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            }
        }),
        $("#complex-header-datatable").DataTable({
            language: {
                paginate: {
                    previous: "<i class='ri-arrow-left-s-line'>",
                    next: "<i class='ri-arrow-right-s-line'>"
                }
            },
            drawCallback: function () {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            },
            columnDefs: [{
                visible: !1,
                targets: -1
            }]
        }),
        $("#row-callback-datatable").DataTable({
            language: {
                paginate: {
                    previous: "<i class='ri-arrow-left-s-line'>",
                    next: "<i class='ri-arrow-right-s-line'>"
                }
            },
            drawCallback: function () {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            },
            createdRow: function (a, e, l) {
                15e4 < +e[5].replace(/[\$,]/g, "") && $("td", a).eq(5).addClass("text-danger");
            }
        }),
        $("#state-saving-datatable").DataTable({
            stateSave: !0,
            language: {
                paginate: {
                    previous: "<i class='ri-arrow-left-s-line'>",
                    next: "<i class='ri-arrow-right-s-line'>"
                }
            },
            drawCallback: function () {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            }
        }),
        $("#fixed-columns-datatable").DataTable({
            scrollY: 300,
            scrollX: !0,
            scrollCollapse: !0,
            paging: !1,
            fixedColumns: !0
        }),
        $(".dataTables_length select").addClass("form-select form-select-sm"),
        $(".dataTables_length label").addClass("form-label");
}),
    $(document).ready(function () {
        var a = $("#fixed-header-datatable").DataTable({
            responsive: !0,
            language: {
                paginate: {
                    previous: "<i class='ri-arrow-left-s-line'>",
                    next: "<i class='ri-arrow-right-s-line'>"
                }
            },
            drawCallback: function () {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            }
        });
        new $.fn.dataTable.FixedHeader(a);
    });