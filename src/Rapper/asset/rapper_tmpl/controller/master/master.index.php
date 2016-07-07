
	/**
    *
    */
	public function act_index () {
	
<? if ($c["usage"] == "form"): ?>
		redirect("page:.entry_form");
<? else: ?>
		redirect("page:.view_list");
<? endif; ?>
	}