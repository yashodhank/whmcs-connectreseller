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
                {/if}

                <div class="col-md-12 " style="text-align: right; padding:10px;">
                    <div  style="float: right; margin-left:5px; display:none;">
                        <form method="post" action="" style="text-align: right;" id='disabletld'>
                            <input type="hidden" value='uncheckall' name='formaction'>
                            <button class="btn btn-danger">{$tplVar['lang']['TLDsStatusDisabledBtn']}</button>
                        </form>
                    </div> 
                    <div  style="float: right; display:none;" >
                        <form method="post" action="" style="text-align: right;" id='enabletld'>
                            <input type="hidden" value='checkall' name='formaction'>
                            <button class="btn btn-success">{$tplVar['lang']['TLDsStatusEnabledBtn']}</button>
                        </form>
                    </div>

                    <div  style="display: flex;gap: 10px;justify-content: flex-end;">
                        <label>Enable/Disable TLDs Status :</label>
                        <input type="checkbox" class="toggle-checkboxs" name="" id="allstatuschange"  data-status="off" 
                        {if $tplVar['checkboxStatus']=='true'}
                            checked
                        {/if}
                        >
                        <label for="allstatuschange" class="toggle-label"></label>
                    </div>
                </div>

                <!-- <div class="rc-actions text-right">
                    <button type="button" class="btn btn-success btn-lg manual_sync">
                        {$tplVar['lang']['manualsync']}
                    </button>
                </div> -->

                <div class="alltld-box">
                    <div class="tld-table">
                        <form method="post" class="all-ssl-tld">
                            <table id="tldTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <!-- <th><input type="hidden" id="domain_id" value=""></th> -->
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