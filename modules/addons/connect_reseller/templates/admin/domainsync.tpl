{include file=$tplVar.header}

<div class="container-box">
    <div class="box light shadow-sm">
        <div class="box-body">
            <div class="domain-box">
                <div class="alldomain-box">
                    <div class="domain-table" hidden>
                        <form method="post" class="all-ssl-domain">
                            <div class="create-domain-box">
                                <div class="create-domain-toolbar">
                                    <button type="button" class="btn btn-success create-domain" disabled
                                        data-bs-toggle="tooltip"
                                        title="This will update the selected TLDs Prices in entire system">
                                        {$tplVar['lang']['importdomain']}
                                    </button>
                                    <div class="progress-bar-container" style="display:none;">
                                        <div class="progress-bar-track">
                                            <div class="progress-bar" style="width: 0%;">
                                                <span class="progress-percent">0%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="domainsync-box">
                                <table id="domainTable" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="selectAll"></th>
                                            <th>{$tplVar['lang']['existtld']}</th>
                                            <th>{$tplVar['lang']['tld']}</th>
                                            <th>
                                                <span class="tld-col-head">
                                                    <span class="tld-col-head__title">{$tplVar['lang']['registration']}</span>
                                                    <span class="tld-col-head__meta">{$tplVar['lang']['price']} · {$tplVar['lang']['cost']} · {$tplVar['lang']['margin']}</span>
                                                </span>
                                            </th>
                                            <th>
                                                <span class="tld-col-head">
                                                    <span class="tld-col-head__title">{$tplVar['lang']['renewal']}</span>
                                                    <span class="tld-col-head__meta">{$tplVar['lang']['price']} · {$tplVar['lang']['cost']} · {$tplVar['lang']['margin']}</span>
                                                </span>
                                            </th>
                                            <th>
                                                <span class="tld-col-head">
                                                    <span class="tld-col-head__title">{$tplVar['lang']['transfer']}</span>
                                                    <span class="tld-col-head__meta">{$tplVar['lang']['price']} · {$tplVar['lang']['cost']} · {$tplVar['lang']['margin']}</span>
                                                </span>
                                            </th>
                                            <th>{$tplVar['lang']['currency_code']}</th>
                                            <th hidden>{$tplVar['lang']['min_period']}</th>
                                            <th hidden>{$tplVar['lang']['max_period']}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
