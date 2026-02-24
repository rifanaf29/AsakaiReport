// KPI Entry - CAPA Management JavaScript

// Toggle CAPA area collapse
function toggleCapaAreaCollapse(areaIndex) {
    const content = document.getElementById(`capa-content-${areaIndex}`);
    const icon = document.getElementById(`collapse-icon-${areaIndex}`);
    
    if (content && icon) {
        const isCollapsed = content.classList.contains('collapsed');
        content.classList.toggle('collapsed');
        icon.classList.toggle('collapsed');
        
        if (!isCollapsed) {
            const areaDiv = document.getElementById(`capa-area-${areaIndex}`);
            if (areaDiv) {
                areaDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }
}

// Collapse all CAPA areas
window.collapseAllCapaAreas = function() {
    const areas = document.querySelectorAll('[id^="capa-content-"]');
    areas.forEach(area => {
        const icon = document.getElementById(`collapse-icon-${area.id.split('-')[2]}`);
        if (!area.classList.contains('collapsed')) {
            area.classList.add('collapsed');
            if (icon) icon.classList.add('collapsed');
        }
    });
    showToast('All CAPA areas collapsed', 'info');
};

// Expand all CAPA areas
window.expandAllCapaAreas = function() {
    const areas = document.querySelectorAll('[id^="capa-content-"]');
    areas.forEach(area => {
        const icon = document.getElementById(`collapse-icon-${area.id.split('-')[2]}`);
        if (area.classList.contains('collapsed')) {
            area.classList.remove('collapsed');
            if (icon) icon.classList.remove('collapsed');
        }
    });
    showToast('All CAPA areas expanded', 'info');
};

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    
    let icon, iconColor;
    if (type === 'success') {
        icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        iconColor = 'text-green-500';
    } else if (type === 'info') {
        icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        iconColor = 'text-blue-500';
    } else {
        icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
        iconColor = 'text-orange-500';
    }
    
    toast.innerHTML = `
        <div class="${iconColor}">${icon}</div>
        <span class="text-sm font-medium">${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// CAPA Dynamic Form Functions
let capaAreaCounter = 0;
let areasList = [];

function toggleCustomAreaInput(areaIndex) {
    const select = document.getElementById(`capa_area_select_${areaIndex}`);
    const input = document.getElementById(`capa_area_name_${areaIndex}`);
    
    if (select && input) {
        if (select.value === '__custom__') {
            input.classList.remove('hidden');
            input.value = '';
            input.focus();
        } else {
            input.classList.add('hidden');
            input.value = select.value;
        }
    }
}

window.addCapaArea = function() {
    const container = document.getElementById('capa-areas-container');
    if (!container) return;
    
    const areaIndex = capaAreaCounter++;
    
    const areaDiv = document.createElement('div');
    areaDiv.className = 'bg-gradient-to-br from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 rounded-xl border-2 border-orange-300 dark:border-orange-700 overflow-hidden shadow-lg mb-6 newly-added';
    areaDiv.id = `capa-area-${areaIndex}`;
    areaDiv.innerHTML = `
        <div class="px-5 py-4 bg-gradient-to-r from-orange-500 to-red-500 flex items-center justify-between">
            <h4 class="font-semibold text-white flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                CAPA Area #${areaIndex + 1}
            </h4>
            <div class="flex items-center space-x-2">
                <button type="button" onclick="toggleCapaAreaCollapse(${areaIndex})" 
                        class="text-white hover:text-yellow-200 hover:bg-orange-600 rounded-lg p-2 transition-colors"
                        title="Collapse/Expand">
                    <svg id="collapse-icon-${areaIndex}" class="w-5 h-5 collapse-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <button type="button" onclick="removeCapaArea(${areaIndex})" 
                        class="text-white hover:text-red-200 hover:bg-red-600 rounded-lg p-2 transition-colors"
                        title="Remove Area">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <div id="capa-content-${areaIndex}" class="capa-area-content">
            <div class="p-5">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            CAPA Date
                        </label>
                        <input type="date" name="capa_areas[${areaIndex}][capa_date]"
                               value="${new Date().toISOString().split('T')[0]}"
                               class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Area Name
                        </label>
                        <select id="capa_area_select_${areaIndex}" 
                                onchange="toggleCustomAreaInput(${areaIndex})"
                                class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
                            <option value="">Select Area</option>
                            ${areasList.map(area => `<option value="${area}">${area}</option>`).join('')}
                            <option value="__custom__">+ Add Custom Area</option>
                        </select>
                        
                        <input type="text" 
                               id="capa_area_name_${areaIndex}" 
                               name="capa_areas[${areaIndex}][area_name]"
                               placeholder="Enter custom area name"
                               class="hidden mt-2 w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Area Description
                    </label>
                    <textarea name="capa_areas[${areaIndex}][area_description]" rows="2"
                              placeholder="Describe the area or context..."
                              class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200"></textarea>
                </div>
                
                <div class="border-t-2 border-orange-300 dark:border-orange-700 pt-4">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-md font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Problems & Root Causes
                        </h5>
                        <button type="button" onclick="addProblem(${areaIndex})" 
                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white text-sm font-medium rounded-lg hover:from-red-700 hover:to-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all shadow-md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Problem
                        </button>
                    </div>
                    <div id="problems-container-${areaIndex}" class="space-y-4"></div>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(areaDiv);
    showToast(`CAPA Area #${areaIndex + 1} added successfully!`, 'success');
    
    setTimeout(() => {
        areaDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(() => {
            areaDiv.classList.remove('newly-added');
            areaDiv.classList.add('newly-added-fade');
        }, 2000);
    }, 100);
    
    setTimeout(() => {
        const areaSelect = document.getElementById(`capa_area_select_${areaIndex}`);
        if (areaSelect) areaSelect.focus();
    }, 600);
};

window.removeCapaArea = function(areaIndex) {
    const area = document.getElementById(`capa-area-${areaIndex}`);
    if (area) {
        if (confirm('Are you sure you want to remove this CAPA area and all its problems?')) {
            area.remove();
            showToast(`CAPA Area #${areaIndex + 1} removed`, 'info');
        }
    }
};

window.addProblem = function(areaIndex) {
    const container = document.getElementById(`problems-container-${areaIndex}`);
    if (!container) return;
    
    const problemCount = container.children.length;
    
    const problemDiv = document.createElement('div');
    problemDiv.className = 'problem-card bg-white dark:bg-gray-800 rounded-xl border-2 border-red-200 dark:border-red-800 overflow-hidden shadow-md newly-added';
    problemDiv.id = `problem-${areaIndex}-${problemCount}`;
    problemDiv.innerHTML = `
        <div class="px-5 py-4 bg-gradient-to-r from-red-100 to-orange-100 dark:from-red-900/30 dark:to-orange-900/30 border-b-2 border-red-200 dark:border-red-800 flex items-center justify-between">
            <h4 class="font-semibold text-red-800 dark:text-red-200 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Problem #${problemCount + 1}
            </h4>
            <button type="button" onclick="removeProblem(${areaIndex}, ${problemCount})" 
                    class="text-red-600 hover:text-red-800 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg p-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>

        <div class="p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Severity Level <span class="text-red-500">*</span>
                </label>
                <select name="capa_areas[${areaIndex}][problems][${problemCount}][severity]" required
                        class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
                    <option value="low">🟢 Low - Minor issue, can be handled easily</option>
                    <option value="medium" selected>🟡 Medium - Requires attention</option>
                    <option value="high">🟠 High - Urgent attention needed</option>
                    <option value="critical">🔴 Critical - Immediate action required</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Problem Description <span class="text-red-500">*</span>
                </label>
                <textarea name="capa_areas[${areaIndex}][problems][${problemCount}][problem_description]" required rows="3"
                          placeholder="Describe the problem in detail..."
                          class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200"></textarea>
            </div>

            <div class="border-t-2 border-orange-200 dark:border-orange-800 pt-4">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-md font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Root Cause Analysis
                    </h5>
                    <button type="button" onclick="addCause(${areaIndex}, ${problemCount})" 
                            class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-xs font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 shadow-sm transition-all">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Cause
                    </button>
                </div>
                <div id="causes-container-${areaIndex}-${problemCount}" class="space-y-3"></div>
            </div>
        </div>
    `;
    
    container.appendChild(problemDiv);
    showToast(`Problem #${problemCount + 1} added to Area #${areaIndex + 1}`, 'success');
    
    setTimeout(() => {
        problemDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(() => {
            problemDiv.classList.remove('newly-added');
            problemDiv.classList.add('newly-added-fade');
        }, 2000);
    }, 100);
    
    addCause(areaIndex, problemCount);
};

window.removeProblem = function(areaIndex, problemIndex) {
    const problem = document.getElementById(`problem-${areaIndex}-${problemIndex}`);
    if (problem) {
        if (confirm('Remove this problem and all its causes?')) {
            problem.remove();
            showToast('Problem removed', 'info');
        }
    }
};

window.addCause = function(areaIndex, problemIndex) {
    const container = document.getElementById(`causes-container-${areaIndex}-${problemIndex}`);
    if (!container) return;
    
    const causeCount = container.children.length;
    
    const causeDiv = document.createElement('div');
    causeDiv.className = 'cause-card bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 mb-3 border-l-4 border-blue-600 shadow-sm';
    causeDiv.id = `cause-${areaIndex}-${problemIndex}-${causeCount}`;
    causeDiv.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <h6 class="text-sm font-semibold text-blue-800 dark:text-blue-200 flex items-center">
                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Root Cause #${causeCount + 1}
            </h6>
            <button type="button" onclick="removeCause(${areaIndex}, ${problemIndex}, ${causeCount})" 
                    class="text-red-600 hover:text-red-800 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg p-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Cause Description <span class="text-red-500">*</span>
            </label>
            <textarea name="capa_areas[${areaIndex}][problems][${problemIndex}][causes][${causeCount}][cause_description]" required rows="2"
                      placeholder="What is the root cause?"
                      class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200"></textarea>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    Action Plans
                </span>
                <button type="button" onclick="addActionPlan(${areaIndex}, ${problemIndex}, ${causeCount})" 
                        class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-xs font-medium rounded-lg hover:from-green-700 hover:to-emerald-700 shadow-sm transition-all">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Action
                </button>
            </div>
            <div id="actions-container-${areaIndex}-${problemIndex}-${causeCount}" class="space-y-3"></div>
        </div>
    `;
    
    container.appendChild(causeDiv);
    addActionPlan(areaIndex, problemIndex, causeCount);
};

window.removeCause = function(areaIndex, problemIndex, causeIndex) {
    const cause = document.getElementById(`cause-${areaIndex}-${problemIndex}-${causeIndex}`);
    if (cause) {
        cause.remove();
    }
};

window.addActionPlan = function(areaIndex, problemIndex, causeIndex) {
    const container = document.getElementById(`actions-container-${areaIndex}-${problemIndex}-${causeIndex}`);
    if (!container) return;
    
    const actionCount = container.children.length;
    
    const actionDiv = document.createElement('div');
    actionDiv.className = 'action-card bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-lg p-4 mb-3 border border-green-300 dark:border-green-700 shadow-sm';
    actionDiv.id = `action-${areaIndex}-${problemIndex}-${causeIndex}-${actionCount}`;
    actionDiv.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-semibold text-green-800 dark:text-green-200 flex items-center">
                <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Action Plan #${actionCount + 1}
            </span>
            <button type="button" onclick="removeAction(${areaIndex}, ${problemIndex}, ${causeIndex}, ${actionCount})" 
                    class="text-red-600 hover:text-red-800 hover:bg-red-100 dark:hover:bg-red-900/50 rounded p-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                    Action Description <span class="text-red-500">*</span>
                </label>
                <textarea name="capa_areas[${areaIndex}][problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][description]" 
                          required rows="2"
                          placeholder="What action will be taken?"
                          class="w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Person in Charge <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="capa_areas[${areaIndex}][problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][person_in_charge]"
                           placeholder="Full name"
                           required
                           class="w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Due Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           name="capa_areas[${areaIndex}][problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][due_date]"
                           required
                           class="w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                    Additional Notes
                </label>
                <input type="text" 
                       name="capa_areas[${areaIndex}][problems][${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][keterangan]"
                       placeholder="Any additional remarks..."
                       class="w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200">
            </div>
        </div>
    `;
    
    container.appendChild(actionDiv);
};

window.removeAction = function(areaIndex, problemIndex, causeIndex, actionIndex) {
    const action = document.getElementById(`action-${areaIndex}-${problemIndex}-${causeIndex}-${actionIndex}`);
    if (action) {
        action.remove();
    }
};

// Initialize areas list
window.initCapaManagement = function(areas) {
    areasList = areas;
};

// Handle form submission - sync all dropdown values before submitting
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const areaContainers = document.querySelectorAll('[id^="capa-area-"]');
            areaContainers.forEach(areaContainer => {
                const areaMatch = areaContainer.id.match(/capa-area-(\d+)/);
                if (areaMatch) {
                    const areaIndex = areaMatch[1];
                    const select = document.getElementById(`capa_area_select_${areaIndex}`);
                    const input = document.getElementById(`capa_area_name_${areaIndex}`);
                    
                    if (select && input && select.value && select.value !== '__custom__') {
                        input.value = select.value;
                    }
                }
            });
        });
    }
});
