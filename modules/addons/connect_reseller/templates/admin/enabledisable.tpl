{include file=$tplVar.header}

<div class="container-box">
    <div class="box light shadow-sm">
        <div class="box-body">
            <div class="tld-box">
                <div class="col-md-12 domainsyncalert">
                    <div class="alert alert-info clearfix" role="alert">
                        {$tplVar['lang']['domain_automation_note']}
                    </div>
                </div>
                {if $tplVar['formSubmitMessage']['status']=='success'}
                    <div class="col-md-12 " style="padding: 0px;">
                        <div class="alert alert-success clearfix" role="alert">
                            {$tplVar['formSubmitMessage']['message']}
                        </div>
                    </div>
                {elseif $tplVar['formSubmitMessage']['status']=='error'}
                    <div class="col-md-12 " style="padding: 0px;">
                        <div class="alert alert-danger clearfix" role="alert">
                            {$tplVar['formSubmitMessage']['message']}
                        </div>
                    </div>
                {/if}

                <div class="col-md-12" style="margin-bottom: 15px;">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>System cron status</strong></div>
                        <div class="panel-body">
                            <p>Price sync last run: {$tplVar.cronStatus.price_last_run|default:'never'}
                                {if $tplVar.cronStatus.price_cursor} (cursor domain_id {$tplVar.cronStatus.price_cursor}){/if}
                                {if $tplVar.cronStatus.price_lock} <span class="text-muted">· lock held</span>{/if}</p>
                            <p>KYC last completed day: {$tplVar.cronStatus.kyc_last_run|default:'never'}
                                {if $tplVar.cronStatus.kyc_cursor} (cursor {$tplVar.cronStatus.kyc_cursor}){/if}
                                {if $tplVar.cronStatus.kyc_lock} <span class="text-muted">· lock held</span>{/if}</p>
                            <form method="post" action="" style="display:inline-block;margin-right:8px;">
                                <input type="hidden" name="token" value="{$tplVar.csrfToken}">
                                <input type="hidden" name="formaction" value="runPriceSyncNow">
                                <button type="submit" class="btn btn-default">Run price sync now</button>
                            </form>
                            <form method="post" action="" style="display:inline-block;">
                                <input type="hidden" name="token" value="{$tplVar.csrfToken}">
                                <input type="hidden" name="formaction" value="runKycNow">
                                <button type="submit" class="btn btn-default">Run KYC now</button>
                            </form>
                        </div>
                    </div>
                </div>

                {if $tplVar.showAutomationEmpty}
                <div class="col-md-12 automation-empty-note" style="padding: 0 0 15px 0;">
                    <div class="alert alert-warning clearfix" role="alert">
                        {$tplVar['lang']['automation_empty']}
                        <a href="{$tplVar.moduleLink}">{$tplVar['lang']['domainsync']}</a>
                    </div>
                </div>
                {else}
                <div class="col-md-12 automation-empty-note hidden" style="display:none;padding: 0 0 15px 0;">
                    <div class="alert alert-warning clearfix" role="alert">
                        {$tplVar['lang']['automation_empty']}
                        <a href="{$tplVar.moduleLink}">{$tplVar['lang']['domainsync']}</a>
                    </div>
                </div>
                {/if}

                <div class="tld-bulk-toggle">
                    <form method="post" action="" id="disabletld" class="hidden">
                        <input type="hidden" name="token" value="{$tplVar.csrfToken}">
                        <input type="hidden" value="uncheckall" name="formaction">
                        <button type="submit" class="btn btn-danger">{$tplVar['lang']['TLDsStatusDisabledBtn']}</button>
                    </form>
                    <form method="post" action="" id="enabletld" class="hidden">
                        <input type="hidden" name="token" value="{$tplVar.csrfToken}">
                        <input type="hidden" value="checkall" name="formaction">
                        <button type="submit" class="btn btn-success">{$tplVar['lang']['TLDsStatusEnabledBtn']}</button>
                    </form>
                    <div class="tld-bulk-toggle__control">
                        <label for="allstatuschange">{$tplVar['lang']['bulk_toggle_label']}</label>
                        <input type="checkbox" class="toggle-checkboxs" name="" id="allstatuschange" data-status="off"
                        {if $tplVar['checkboxStatus']=='true'}
                            checked
                        {/if}
                        >
                        <label for="allstatuschange" class="toggle-label"></label>
                    </div>
                </div>

                <div class="alltld-box">
                    <div class="tld-table">
                        <form method="post" class="all-ssl-tld">
                            <table id="tldTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{$tplVar['lang']['tld']}</th>
                                        <th>{$tplVar['lang']['status']}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
