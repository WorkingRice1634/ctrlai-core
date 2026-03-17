<div id="kboard-default-latest">
	<table>
		<thead>
			<tr>
				<th class="kboard-latest-title"><?php echo __('Title', 'kboard')?></th>
				<th class="kboard-latest-date"><?php echo __('Date', 'kboard')?></th>
			</tr>
		</thead>
		<tbody>
			<?php while($content = $list->hasNext()):?>
			
			<?php 
			// ★ 24시간 만료 체크 로직 추가 (메인 최신글용) ★
			$is_premium = $content->option->premium; 
			$is_paid_valid = false;
			
			if($is_premium && is_user_logged_in()){
				$user_id = get_current_user_id();
				$purchase_history = get_user_meta($user_id, 'ctrlai_premium_purchases', true);
				if(!is_array($purchase_history)) $purchase_history = array();
				
				if(isset($purchase_history[$content->uid])){
					$purchase_time = $purchase_history[$content->uid];
					// 86400초(24시간) 이내라면 유효
					if((time() - $purchase_time) < (24 * 60 * 60)){
						$is_paid_valid = true;
					}
				}
			}
			?>

			<tr>
				<td class="kboard-latest-title">
					<a href="<?php echo esc_url($url->getDocumentURLWithUID($content->uid))?>"
					   <?php if($is_premium && !$content->isEditor() && !$board->isAdmin() && !$is_paid_valid):?>
					   onclick="if(!confirm('👑 100포인트가 필요한 글입니다.\n포인트를 사용하여 열람하시겠습니까?')){return false;} else { this.href += '&buy=1'; }"
					   <?php endif?>
					>
						<div class="kboard-default-cut-strings">
							
							<?php echo $content->title?>
							
							<?php if($is_premium):?>
								<span style="color: #ffdd57; font-weight: bold; margin-left: 5px; background: #333; padding: 2px 6px; border-radius: 4px; font-size: 11px; vertical-align: middle;">👑 Premium</span>
							<?php endif?>
							
							<span class="kboard-comments-count"><?php echo $content->getCommentsCount()?></span>
						</div>
					</a>
				</td>
				<td class="kboard-latest-date"><?php echo $content->getDate()?></td>
			</tr>
			<?php endwhile?>
		</tbody>
	</table>
</div>