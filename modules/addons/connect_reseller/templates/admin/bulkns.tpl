{include file=$tplVar.header}

<div class="container-box">
    <div class="box light shadow-sm">
        <div class="box-body">
            <div class="tld-box">
                <div class="col-md-12">
                    <div class="alert alert-info clearfix" role="alert">
                        {$tplVar['lang']['bulkns_note']}
                    </div>
                </div>
                {if $tplVar['formSubmitMessage']['status']=='success'}
                    <div class="col-md-12" style="padding: 0px;">
                        <div class="alert alert-success clearfix" role="alert">
                            {$tplVar['formSubmitMessage']['message']|escape}
                        </div>
                    </div>
                {elseif $tplVar['formSubmitMessage']['status']=='error'}
                    <div class="col-md-12" style="padding: 0px;">
                        <div class="alert alert-danger clearfix" role="alert">
                            {$tplVar['formSubmitMessage']['message']|escape}
                        </div>
                    </div>
                {/if}

                <div class="col-md-12">
                    <form method="post" action="">
                        <input type="hidden" name="token" value="{$tplVar.csrfToken}">
                        <input type="hidden" name="formaction" value="runBulkNs">

                        <div class="form-group">
                            <label for="cr-bulk-domains">{$tplVar['lang']['bulkns_domains']}</label>
                            <textarea id="cr-bulk-domains" name="domains" class="form-control" rows="6" required placeholder="example.com&#10;example.net"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="cr-ns1">Nameserver 1 *</label>
                            <input type="text" class="form-control" id="cr-ns1" name="nameserver1" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns2">Nameserver 2 *</label>
                            <input type="text" class="form-control" id="cr-ns2" name="nameserver2" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns3">Nameserver 3</label>
                            <input type="text" class="form-control" id="cr-ns3" name="nameserver3" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns4">Nameserver 4</label>
                            <input type="text" class="form-control" id="cr-ns4" name="nameserver4" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns5">Nameserver 5</label>
                            <input type="text" class="form-control" id="cr-ns5" name="nameserver5" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns6">Nameserver 6</label>
                            <input type="text" class="form-control" id="cr-ns6" name="nameserver6" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns7">Nameserver 7</label>
                            <input type="text" class="form-control" id="cr-ns7" name="nameserver7" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns8">Nameserver 8</label>
                            <input type="text" class="form-control" id="cr-ns8" name="nameserver8" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns9">Nameserver 9</label>
                            <input type="text" class="form-control" id="cr-ns9" name="nameserver9" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns10">Nameserver 10</label>
                            <input type="text" class="form-control" id="cr-ns10" name="nameserver10" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns11">Nameserver 11</label>
                            <input type="text" class="form-control" id="cr-ns11" name="nameserver11" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns12">Nameserver 12</label>
                            <input type="text" class="form-control" id="cr-ns12" name="nameserver12" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="cr-ns13">Nameserver 13</label>
                            <input type="text" class="form-control" id="cr-ns13" name="nameserver13" autocomplete="off">
                        </div>

                        <button type="submit" class="btn btn-primary">{$tplVar['lang']['bulkns_submit']}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
