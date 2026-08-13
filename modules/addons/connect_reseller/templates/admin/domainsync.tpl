{include file=$tplVar.header}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">


<div class="container-box">
    <div class="box light shadow-sm">
        <div class="box-body">
            <div class="domain-box">
                <div class="rc-actions text-right">
                    <!-- <button type="button" class="btn btn-success btn-lg domain_sync">
                        {$tplVar['lang']['domainsync']}
                    </button> -->
                </div>

                <div class="alldomain-box">

                    <div class="domain-table" hidden>
                        <form method="post" class="all-ssl-domain">
                            <div class="create-domain-box">
                                <div style="display: flex; gap: 10px;">
                                    <div >
                                        <button type="button" data-toggle="modal" class="btn btn-success create-domain"
                                            data-bs-toggle="tooltip"
                                            title="This will update the selected TLDs Prices in entire system">
                                            {$tplVar['lang']['importdomain']}
                                        </button>
                                    </div>
                                    <div  style="width: 301px;">
                                        <div class="progress-bar-container" style="display:none; margin-top: 10px;">
                                            <div
                                                style="background: #e0e0e0; height: 20px; border-radius: 5px; overflow: hidden;">
                                                <div class="progress-bar"
                                                    style="height: 100%; background: #3498db; width: 0%; transition: width 0.3s; text-align: center;">
                                                    <span class="progress-percent"  style="color:#fff; display:inline-block">0%</span>
                                                </div>
                                               
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
                                            <th><span class="tld-pricingg-td">
                                                    <span class="local-pricing">
                                                        {$tplVar['lang']['registration_price']}</span>
                                                    <span class="remote-pricing">
                                                        {$tplVar['lang']['cost']} </span>
                                                </span>
                                                <span class="tld-margin-heading">Margin</span>
                                            </th>
                                            <th><span class="tld-pricingg-td">
                                                    <span class="local-pricing">
                                                        {$tplVar['lang']['renewal_price']}</span>
                                                    <span class="remote-pricing">
                                                        {$tplVar['lang']['cost']} </span>
                                                </span>
                                                <span class="tld-margin-heading">Margin</span>
                                            </th>
                                            <th><span class="tld-pricingg-td">
                                                    <span class="local-pricing">
                                                        {$tplVar['lang']['transfer_price']}</span>
                                                    <span class="remote-pricing">
                                                        {$tplVar['lang']['cost']} </span>
                                                </span>
                                                <span class="tld-margin-heading">Margin</span>
                                            </th>
                                            <th>{$tplVar['lang']['currency_code']}</th>
                                            <th hidden>{$tplVar['lang']['min_period']}</th>
                                            <th hidden>{$tplVar['lang']['max_period']}</th>
                                        </tr>

                                        {*<tr>
                                            <th><input type="checkbox" id="selectAll"></th>
                                            <th>{$tplVar['lang']['existtld']}</th>
                                            <th>{$tplVar['lang']['tld']}</th>
                                            <th>
                                                <!-- <span class="inline-block tld-pricing">
                                                    <span class="local-pricing">{$tplVar['lang']['current']}</span><br>
                                                    <span class="remote-pricing">{$tplVar['lang']['cost']}</span>
                                                </span> -->
                                                <span class="tld-margins">{$tplVar['lang']['margin']}</span>
                                            </th>
                                            <th>
                                                <!-- <span class="inline-block tld-pricing">
                                                    <span class="local-pricing">{$tplVar['lang']['current']}</span><br>
                                                    <span class="remote-pricing">{$tplVar['lang']['cost']}</span>
                                                </span> -->
                                                <span class="tld-margins">{$tplVar['lang']['margin']}</span>
                                            </th>
                                            <th>
                                                <!-- <span class="inline-block tld-pricing">
                                                    <span class="local-pricing">{$tplVar['lang']['current']}</span><br>
                                                    <span class="remote-pricing">{$tplVar['lang']['cost']}</span>
                                                </span> -->
                                                <span class="tld-margins">{$tplVar['lang']['margin']}</span>
                                            </th>
                                            <th>{$tplVar['lang']['currency_code']}</th>
                                        </tr>*}

                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                            </div>
                            <div class="create-domain-box" style="display: flex;gap: 10px;">
                                <div>
                                <button type="button" data-toggle="modal" class="btn btn-success create-domain"
                                    data-bs-toggle="tooltip"
                                    title="This will update the selected TLDs Prices in entire system">
                                    {$tplVar['lang']['importdomain']}
                                </button>
                                </div>
                                <div  style="width: 301px;">
                                        <div class="progress-bar-container" style="display:none; margin-top: 10px;">
                                            <div
                                                style="background: #e0e0e0; height: 20px; border-radius: 5px; overflow: hidden;">
                                                <div class="progress-bar"
                                                    style="height: 100%; background: #3498db; width: 0%; transition: width 0.3s; text-align: center;">
                                                    <span class="progress-percent"  style="color:#fff; display:inline-block">0%</span>
                                                </div>
                                               
                                            </div>
                                            
                                        </div>

                                    </div>

                            </div>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>