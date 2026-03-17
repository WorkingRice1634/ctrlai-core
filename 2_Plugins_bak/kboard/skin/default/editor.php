<div id="kboard-default-editor">
	<form class="kboard-form" method="post" action="<?php echo esc_url($url->getContentEditorExecute())?>" enctype="multipart/form-data" onsubmit="return kboard_editor_execute(this);">

	<div class="dc-board-header">
		<div class="dc-board-title">
			<?php echo esc_html($board->board_name); ?>
		</div>
		<?php if(!empty($board->description)): ?>
		<div class="dc-board-desc">
			<?php echo esc_html($board->description); ?>
		</div>
		<?php endif; ?>
	</div>

	<?php $skin->editorHeader($content, $board)?>
		
		<div class="dc-title-box">
			<input type="text" name="title" value="<?php echo esc_attr($content->title)?>" placeholder="제목을 입력해 주세요.">
		</div>

		<div class="dc-title-box ctrlai-tag-wrapper" style="margin-top: 5px; position: relative; border: none; padding: 0;">
			
			<input type="hidden" name="kboard_option_tags" id="real_tags_input" value="<?php echo esc_attr($content->option->tags)?>">
			
			<div id="visual_tags_container" class="visual-tags-container">
				<input type="text" id="tag_search_input" placeholder="🏷️ AI 툴 검색 (입력 후 목록 선택 또는 엔터)" autocomplete="off">
			</div>

			<ul id="tag_dropdown" class="tag-dropdown"></ul>

			<div style="font-size: 12px; color: #8894a8; margin-top: 5px; padding-left: 5px;">
				💡 공식 프로그램은 하단 목록에서 <b>선택</b>하시고, 없는 프로그램은 입력 후 <b>엔터(Enter)</b>를 누르세요.
			</div>
		</div>
		<div class="dc-warning-box" style="margin-top: 10px;">
			※ 음란물, 차별, 비하, 혐오 및 초상권, 저작권 침해 게시물은 민, 형사상의 책임을 질 수 있습니다.
		</div>

		<div class="dc-ad-box">
			<span>📢 여기에 광고가 들어갑니다 (Google AdSense)</span>
		</div>

		<div class="dc-toolbar-box">
			<button type="button" class="dc-tool-btn" onclick="kboard_editor_open_media(); return false;">
				📷 이미지
			</button>
		</div>

		<div class="dc-editor-box">
			<?php wp_editor($content->content, 'kboard_content', array(
				'media_buttons' => false,
				'editor_height' => 450,
				'textarea_name' => 'kboard_content',
				'tinymce' => array(
					'toolbar1' => 'fontselect fontsizeselect | bold italic underline strikethrough | forecolor | alignleft aligncenter alignright | link unlink',
					'toolbar2' => '',
					'toolbar3' => ''
				),
				'quicktags' => false
			)); ?>
		</div>
		
		<div class="dc-prompt-box" style="margin-top: 15px;">
			<div style="color: #ffdd57; font-weight: bold; margin-bottom: 8px; font-size: 14px;">
				✨ 프롬프트 입력 (자동으로 블러 처리되며 복사 버튼이 생성됩니다)
			</div>
			<textarea name="kboard_option_prompt" placeholder="여기에 프롬프트를 입력하세요. (영문, 한글 모두 가능)" style="width: 100%; height: 120px; background: #0f1520; border: 1px solid #1e2a3e; color: #d0d7e1; padding: 12px; border-radius: 4px; resize: vertical; font-family: monospace; font-size: 13px;"><?php echo esc_textarea($content->option->prompt)?></textarea>
		</div>
		
		<div class="dc-checkbox-area">
			<?php if($board->isAdmin()): ?>
			<label class="dc-check-item">
				<input type="checkbox" name="notice" value="true" <?php if($content->notice) echo 'checked';?>>
				<span class="check-label">📢 공지사항</span>
			</label>
			<?php endif; ?>

			<?php if($board->id != '9'): ?>
			<label class="dc-check-item">
				<input type="hidden" name="kboard_option_premium" value="">
				<input type="checkbox" name="kboard_option_premium" value="1" <?php if($content->option->premium) echo 'checked';?>>
				<span class="check-label">👑 프리미엄</span>
			</label>
			<?php endif?>
		</div>

		<div class="kboard-control">
			<div class="right">
				<a href="<?php echo esc_url($url->getBoardList())?>" class="dc-btn-cancel">취소</a>
				<button type="submit" class="dc-btn-submit">등록</button>
			</div>
		</div>
	</form>
</div>

<style>
/* 태그 입력창 전체 스타일 */
.visual-tags-container {
    display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
    background: #0f1520; border: 1px solid #1e2a3e; padding: 8px 10px; border-radius: 4px; min-height: 45px; cursor: text;
}
/* 실제 텍스트 입력 부분 */
.visual-tags-container input {
    border: none !important; background: transparent !important; color: #fff !important; flex: 1; min-width: 150px; padding: 0 !important; font-size: 14px; box-shadow: none !important; height: auto !important; margin: 0 !important;
}
.visual-tags-container input:focus { outline: none !important; }

/* 토큰(뱃지) 스타일 */
.ctrlai-tag-token {
    display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; animation: fadeIn 0.2s;
}
.ctrlai-tag-token.official { background-color: #ffdd57; color: #1e2a3e; }
.ctrlai-tag-token.custom { background-color: #2a3546; color: #8894a8; }
.ctrlai-tag-token .remove-token {
    margin-left: 6px; cursor: pointer; font-size: 14px; line-height: 1; opacity: 0.7; transition: 0.2s;
}
.ctrlai-tag-token .remove-token:hover { opacity: 1; color: #ff0000; }

/* 드롭다운 목록 스타일 */
.tag-dropdown {
    position: absolute; top: calc(100% + 5px); left: 0; right: 0; background: #172130; border: 1px solid #3d4a5d; border-radius: 4px; max-height: 200px; overflow-y: auto; z-index: 999; display: none; list-style: none; padding: 0; margin: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.5);
}
.tag-dropdown li {
    padding: 10px 15px; cursor: pointer; color: #d0d7e1; font-size: 13px; border-bottom: 1px solid #1e2a3e; display: flex; justify-content: space-between; align-items: center;
}
.tag-dropdown li:hover { background: #2a3546; }
.tag-dropdown li.disabled { color: #5c7090; cursor: not-allowed; background: transparent; }
.tag-dropdown li .status-msg { font-size: 11px; color: #ff5555; }
@keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>

<?php wp_enqueue_script('kboard-default-script', "{$skin_path}/script.js", array(), KBOARD_VERSION, true)?>

<script>
jQuery(document).ready(function($){
	// 미리보기 방지 관련 충돌 로직 삭제 완료

	// ==========================================
	// 2. 고급 태그 토큰 시스템 로직 (DB 연동 완료)
	// ==========================================
	<?php
	$db_tags = array();
	$terms = get_terms(array(
	    'taxonomy' => 'ai_program', 
	    'hide_empty' => false,
	));
	if(!is_wp_error($terms) && !empty($terms)){
	    foreach($terms as $term){
	        $db_tags[] = $term->name;
	    }
	}
	?>
	var officialTags = <?php echo json_encode($db_tags); ?>;
	var currentTags = []; 

	var $realInput = $('#real_tags_input');
	var $visualContainer = $('#visual_tags_container');
	var $searchInput = $('#tag_search_input');
	var $dropdown = $('#tag_dropdown');

	var initialTags = $realInput.val();
	if(initialTags) {
		currentTags = initialTags.split(',').map(function(item) { return item.trim(); }).filter(Boolean);
		renderTokens();
	}

	$visualContainer.on('click', function(){ $searchInput.focus(); });

	$searchInput.on('input focus', function(){
		var keyword = $(this).val().trim().toLowerCase();
		$dropdown.empty(); 
		var hasResults = false;

		officialTags.forEach(function(tag){
			if(tag.toLowerCase().includes(keyword) || keyword === '') {
				hasResults = true;
				var isAlreadyAdded = currentTags.includes(tag);
				var $li = $('<li>').text(tag);
				
				if(isAlreadyAdded) {
					$li.addClass('disabled');
					$li.append('<span class="status-msg">적용됨</span>');
				} else {
					$li.on('click', function(){
						addTag(tag);
						$searchInput.val('');
						$dropdown.hide();
					});
				}
				$dropdown.append($li);
			}
		});

		if(hasResults) { $dropdown.show(); } else { $dropdown.hide(); }
	});

	$searchInput.on('keydown', function(e){
		if(e.key === 'Enter' || e.key === ',') {
			e.preventDefault(); 
			var newTag = $(this).val().trim();
			if(newTag) {
				var index = currentTags.findIndex(tag => tag.toLowerCase() === newTag.toLowerCase());
				if(index === -1) {
					addTag(newTag);
				} else {
					alert('이미 추가된 태그입니다.');
				}
				$(this).val('');
				$dropdown.hide();
			}
		}
		if(e.key === 'Backspace' && $(this).val() === '') {
			if(currentTags.length > 0) {
				currentTags.pop();
				renderTokens();
				updateRealInput();
			}
		}
	});

	$(document).on('click', function(e){
		if(!$(e.target).closest('.ctrlai-tag-wrapper').length) {
			$dropdown.hide();
		}
	});

	function addTag(tag) {
		currentTags.push(tag);
		renderTokens();
		updateRealInput();
		$searchInput.focus();
	}

	function renderTokens() {
		$visualContainer.find('.ctrlai-tag-token').remove(); 
		currentTags.forEach(function(tag, index){
			var isOfficial = officialTags.includes(tag);
			var tokenClass = isOfficial ? 'official' : 'custom';
			
			var $token = $('<span>').addClass('ctrlai-tag-token ' + tokenClass).text(tag);
			var $removeBtn = $('<span>').addClass('remove-token').html('&times;').on('click', function(e){
				e.stopPropagation(); 
				currentTags.splice(index, 1);
				renderTokens();
				updateRealInput();
			});
			$token.append($removeBtn);
			$token.insertBefore($searchInput); 
		});
	}

	function updateRealInput() {
		$realInput.val(currentTags.join(','));
	}
});
</script>