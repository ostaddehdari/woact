export function decideRenderMode({
  globalMode = 'hybrid',
  override = 'inherit',
  isBuilderDocument = false,
  preserveBuilders = true,
  postType = '',
  enabledPostTypes = [],
}) {
  if (globalMode === 'classic' || override === 'classic') return 'classic';
  if (override === 'react') return 'react';
  if (preserveBuilders && isBuilderDocument) return 'classic';
  if (globalMode === 'react' && enabledPostTypes.includes(postType)) return 'react';
  return 'classic';
}

