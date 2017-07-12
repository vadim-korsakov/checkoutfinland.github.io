require 'spec_helper'

describe 'index', :type => :feature do
  before do
    visit '/'
  end

  it 'has the correct title header' do
    expect(page).to have_selector 'h1'
    within 'h1#introduction' do
      expect(page).to have_content /Introduction/i
    end
  end

  it 'has a paragraph in there too' do
    expect(page).to have_selector 'p'
  end
end
