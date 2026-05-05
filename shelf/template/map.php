<?php $this->content = function($v) { ?>

<style>
    .map-container {
        display: flex;
        flex-direction: row;
        gap: 2rem;
        width: 100%;
        box-sizing: border-box;
    }
    @media (max-width: 1024px) {
        .map-container {
            flex-direction: column;
        }
    }
    .map-header {
        background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
        padding: 2rem;
        border-radius: 1rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    .map-header h1 {
        font-size: 2rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }
    .map-header p {
        color: #e0e7ff;
        margin-top: 0.5rem;
        margin-bottom: 0;
        font-size: 1rem;
        opacity: 0.9;
    }
    .map-main-area {
        flex: 3;
        min-height: 500px;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .dark .map-main-area {
        background-color: #1e293b;
        border-color: #334155;
    }
    .map-empty-state {
        text-align: center;
        padding: 2.5rem;
        background-color: rgba(255, 255, 255, 0.9);
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(8px);
        max-width: 400px;
        z-index: 5;
    }
    .dark .map-empty-state {
        background-color: rgba(30, 41, 59, 0.9);
        border-color: #475569;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
    }
    .map-sidebar {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        min-width: 280px;
    }
    .map-card {
        background-color: white;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .dark .map-card {
        background-color: #1e293b;
        border-color: #334155;
        color: #f1f5f9;
    }
    .map-stat-grid {
        display: grid;
        grid-template-cols: 1fr;
        gap: 1rem;
    }
    .map-stat-box {
        padding: 1.25rem;
        border-radius: 0.75rem;
        font-weight: bold;
    }
    .map-stat-primary {
        background-color: #e0e7ff;
        color: #3730a3;
        border: 1px solid #c7d2fe;
    }
    .dark .map-stat-primary {
        background-color: #27272a;
        color: #c7d2fe;
        border-color: #3730a3;
    }
    .map-stat-success {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .dark .map-stat-success {
        background-color: #27272a;
        color: #a7f3d0;
        border-color: #065f46;
    }
</style>

<div x-data="shelfMap()" style="box-sizing: border-box; padding: 1.5rem; max-width: 80rem; margin-left: auto; margin-right: auto;">
    
    <!-- Header Section -->
    <div class="map-header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4">
            <div>
                <h1>
                    <svg style="width: 2.5rem; height: 2.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A2 2 0 013 15.382V6.618a2 2 0 011.553-1.944L9 2l6 3 5.447-2.724A2 2 0 0121 4.618v8.764a2 2 0 01-1.553 1.944L15 18l-6 2z" />
                    </svg>
                    <span>Shelf Map</span>
                </h1>
                <p>Precision warehouse visualization & spatial inventory management.</p>
            </div>
            <div>
                <a href="./shelf/simple/" class="btn btn-light d-inline-flex align-items-center gap-2">
                    <i class="ti ti-plus" aria-hidden="true"></i>
                    Configure Shelves
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="map-container">
        
        <!-- Map Visualization (Left) -->
        <div class="map-main-area">
            
            <!-- Empty State -->
            <div class="map-empty-state" x-show="!mapImage">
                <div class="avatar avatar-xl rounded-circle bg-light text-secondary mx-auto mb-4">
                    <svg style="width: 2rem; height: 2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="fs-4 fw-bold mb-2">Visual Map Pending</h3>
                <p class="text-muted small mb-4 lh-base">Your warehouse layout isn't loaded yet. Upload a blueprint to start pinning locations.</p>
                <a href="./shelf/simple/" class="fw-bold small link-primary">
                    Open Shelf Setup &rarr;
                </a>
            </div>

            <!-- Interactive Map Area -->
            <div x-show="mapImage" @wheel.prevent="zoom($event)" style="position: relative; width: 100%; height: 100%; min-height: 500px; cursor: crosshair; overflow: hidden;">
                <img :src="mapImage" :style="`transform: scale(${zoomLevel});`" @mousedown.prevent style="width: 100%; max-width: 100%; height: auto; display: block; user-select: none; transform-origin: center; transition: transform 0.2s;">
                
                <!-- Pins -->
                <template x-for="pin in pins" :key="pin.id">
                    <div
                        style="position: absolute; width: 2.5rem; height: 2.5rem; margin-left: -1.25rem; margin-top: -1.25rem; z-index: 10; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.3s;"
                        :style="`left: ${pin.x * 100}%; top: ${pin.y * 100}%;`"
                        @click="selectedPin = pin"
                    >
                        <div style="position: absolute; inset: 0; background-color: #4f46e5; border-radius: 50%; opacity: 0.2;"></div>
                        <div style="position: relative; background-color: #4f46e5; color: white; width: 1.75rem; height: 1.75rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.2); border: 2px solid white; font-size: 0.625rem; font-weight: 900;" x-text="pin.code"></div>
                        
                        <!-- Mini Tooltip -->
                        <div style="position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 0.5rem; background-color: #1e293b; color: white; font-size: 0.625rem; padding: 0.25rem 0.75rem; border-radius: 9999px; white-space: nowrap; box-shadow: 0 10px 15px rgba(0,0,0,0.3); pointer-events: none;" class="tooltip-text">
                            <span x-text="pin.name"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Sidebar / Stats (Right) -->
        <div class="map-sidebar">
            
            <!-- Pin Details (Top Priority if selected) -->
            <div x-show="selectedPin" class="map-card position-relative">
                <div class="position-absolute top-0 end-0 p-3">
                    <button type="button" @click="selectedPin = null" class="btn-close" aria-label="Close"></button>
                </div>

                <div class="mb-4">
                    <span class="badge bg-indigo-lt text-uppercase mb-2" x-text="selectedPin?.type"></span>
                    <h4 class="h3 fw-bold lh-sm m-0" x-text="selectedPin?.name"></h4>
                </div>

                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-2">
                        <span class="text-muted fw-bold small text-uppercase">Code</span>
                        <span class="font-monospace fw-bold" x-text="selectedPin?.code"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-2">
                        <span class="text-muted fw-bold small text-uppercase">Status</span>
                        <span class="badge bg-success-lt" x-text="selectedPin?.status"></span>
                    </div>

                    <div class="d-flex flex-column gap-3 pt-3">
                        <a :href="`./item/list?location=${selectedPin?.id}`" class="btn btn-dark w-100">
                            View Inventory &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Global Stats -->
            <div class="map-card d-flex flex-column gap-4">
                <div>
                    <h4 class="text-muted fw-bold small text-uppercase mb-3">Coverage Insights</h4>
                    <div class="progress" style="height: 0.5rem;">
                        <div class="progress-bar bg-indigo" role="progressbar"
                             :style="`width: ${pins.length > 0 ? (pins.filter(p => p.x !== null).length / pins.length * 100) : 0}%; transition: width 1s;`"
                             :aria-valuenow="pins.filter(p => p.x !== null).length"
                             aria-valuemin="0" :aria-valuemax="pins.length"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted fw-bold" style="font-size: 0.625rem; text-transform: uppercase;">
                            <span x-text="pins.filter(p => p.x !== null).length"></span> Pinned
                        </span>
                        <span class="fw-bold text-indigo" style="font-size: 0.625rem; text-transform: uppercase;" x-text="pins.length > 0 ? Math.round((pins.filter(p => p.x !== null).length / pins.length) * 100) + '%' : '0%'"></span>
                    </div>
                </div>

                <div class="map-stat-grid">
                    <div class="map-stat-box map-stat-primary">
                        <div class="small text-uppercase mb-1" style="opacity: 0.8;">Total Assets</div>
                        <div class="fw-black" style="font-size: 1.75rem;" x-text="pins.length"></div>
                    </div>
                    <div class="map-stat-box map-stat-success">
                        <div class="small text-uppercase mb-1" style="opacity: 0.8;">Active Now</div>
                        <div class="fw-black" style="font-size: 1.75rem;" x-text="pins.filter(p => p.status === 'available').length"></div>
                    </div>
                </div>
            </div>

            <!-- Shortcuts -->
            <div class="map-card text-center">
                <p class="text-muted fw-bold small text-uppercase mb-3">Navigation Tips</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <span class="badge bg-light text-secondary rounded-pill">Scroll to Zoom</span>
                    <span class="badge bg-light text-secondary rounded-pill">Click to Detail</span>
                    <span class="badge bg-light text-secondary rounded-pill">Drag to Pan</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function shelfMap() {
    return {
        mapImage: null,
        zoomLevel: 1,
        pins: <?php 
            $data = array_map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code->toString(),
                'name' => $p->name,
                'x' => $p->mapXRatio,
                'y' => $p->mapYRatio,
                'type' => $p->locationType->value,
                'status' => $p->operationalStatus->value,
            ], $v->pins);
            echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>,
        selectedPin: null,
        init() {
            this.mapImage = localStorage.getItem('saso_warehouse_map');
        },
        zoom(e) {
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            this.zoomLevel = Math.min(Math.max(0.5, this.zoomLevel + delta), 3);
        }
    }
}
</script>
<?php } ?>
